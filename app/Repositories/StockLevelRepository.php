<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockLevelRepository
{
    public function getLocked(Product $product, Warehouse $warehouse): StockLevel
    {
        $level = $product->stockLevels()
            ->whereBelongsTo($warehouse)
            ->lockForUpdate()
            ->first();

        if ($level) {
            return $level;
        }

        return $product->stockLevels()->create([
            'tenant_id' => $product->tenant_id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 0,
        ]);
    }

    public function clear(Product $product, Warehouse $warehouse): void
    {
        $product->stockLevels()->whereBelongsTo($warehouse)->delete();
    }

    public function clearForTenant(Tenant $tenant): void
    {
        $tenant->stockLevels()->delete();
    }

    public function quantity(Product $product, Warehouse $warehouse): int
    {
        return (int) $product->stockLevels()
            ->whereBelongsTo($warehouse)
            ->value('quantity');
    }

    public function count(Tenant $tenant): int
    {
        return $tenant->stockLevels()->count();
    }

    public function insertBatch(array $rows): void
    {
        DB::table('stock_levels')->insert($rows);
    }

    public function paginate(?Product $product, ?Warehouse $warehouse, int $perPage = 15): LengthAwarePaginator
    {
        $query = StockLevel::query()
            ->with(['product', 'warehouse']);

        if ($product) {
            $query->whereBelongsTo($product);
        }

        if ($warehouse) {
            $query->whereBelongsTo($warehouse);
        }

        if ($product && !$warehouse) {
            $query->orderBy('warehouse_id');
        } elseif ($warehouse && !$product) {
            $query->orderBy('product_id');
        } else {
            $query->orderBy('id');
        }

        return $query->paginate($perPage);
    }
}
