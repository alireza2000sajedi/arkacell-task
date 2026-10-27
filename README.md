# ArkaCell API

Laravel + MySQL multi tenant api.
## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
```

```sql
CREATE DATABASE arkacell_task;
CREATE DATABASE arkacell_testing;
```

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Detect tenant with this header:
```http
X-Subdomain: app
```

## Stock Movements

Stock movements are done inside a database transaction. I use `FOR UPDATE` to lock the stock row while updating it, so two requests dont change the stock at the same time.

If there is not enough stock, the operation will fail. This also works for transfers, so stock cant go below zero.

For a transfer, both warehouses are updated in the same transaction. The movement also saves `from_warehouse_id` and `to_warehouse_id` in the meta field.

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/stock-movements` | Create a stock movement |
| GET | `/api/stock-levels` | Get stock levels |
| GET | `/api/products/{sku}/history` | Get product movement history |

The stock levels endpoint supports some filters like `product_id` and `warehouse_id`. You can also use `per_page` to change the number of results.

## Reconcile Command

Run this command to check stock quantities:

```bash
php artisan inventory:reconcile 1
```

Or use the tenant subdomain:

```bash
php artisan inventory:reconcile acme
```

The command calculates the stock quantity from movements and compares it with `stock_levels`. If something is different, it shows the mismatches in the output.

It only checks the data by default and doesnt change anything, so you can run it again when needed.

## Tests

The tests use the `arkacell_testing` database automatically. There is no need to set the database environment variables manually.

Run all tests:

```bash
php artisan test
```

For the concurrency test:

```bash
php artisan test --filter=StockConcurrencyTest
```

This test checks 100 parallel stock-out requests when there are only 50 items available. The expected result is 50 successful requests, 50 failed requests, and zero stock left.

Performance test (optional):

```bash
php artisan test --group=performance
```

This test is used for checking performance with around 1 million rows. The target is under 200ms.

## Indexes

I added composite indexes to `stock_levels` and `stock_movements` for the queries used most often, like filtering by tenant, product or warehouse and getting movement history.

There are also unique indexes on:

- `products`: `(tenant_id, sku)`
- `stock_levels`: `(tenant_id, product_id, warehouse_id)`

This prevents duplicate products for the same tenant and duplicate stock records for the same product and warehouse.
