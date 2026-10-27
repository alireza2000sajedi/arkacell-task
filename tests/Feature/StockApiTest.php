<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_and_levels(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);

        $this->postJson('/api/stock-movements', [
            'type' => 'in',
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse1->id,
            'quantity' => 10,
        ], ['X-Subdomain' => 'app1'])
            ->assertCreated()
            ->assertJsonPath('data.quantity', 10);

        $this->getJson('/api/stock-levels?product_id='.$product1->id, ['X-Subdomain' => 'app1'])
            ->assertOk()
            ->assertJsonPath('data.0.quantity', 10);
    }

    public function test_product_history(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);
        $warehouse2 = Warehouse::factory()->create(['tenant_id' => $app1->id]);

        $this->postJson('/api/stock-movements', [
            'type' => 'in',
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse1->id,
            'quantity' => 5,
        ], ['X-Subdomain' => 'app1'])->assertCreated();

        $this->postJson('/api/stock-movements', [
            'type' => 'transfer',
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'quantity' => 2,
        ], ['X-Subdomain' => 'app1'])->assertCreated();

        $this->getJson('/api/products/product1/history', ['X-Subdomain' => 'app1'])
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_out_without_stock(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);

        $this->postJson('/api/stock-movements', [
            'type' => 'out',
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse1->id,
            'quantity' => 1,
        ], ['X-Subdomain' => 'app1'])->assertStatus(422);
    }
}
