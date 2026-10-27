<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReconcileTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_finds_mismatch(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        set_tenant($app1);

        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);

        app(StockMovementService::class)->in($product1, $warehouse1, 40);

        $product1->stockLevels()->whereBelongsTo($warehouse1)->update(['quantity' => 42]);

        $this->artisan('inventory:reconcile', ['tenant' => 'app1'])
            ->expectsOutputToContain('mismatch')
            ->assertSuccessful();
    }
}
