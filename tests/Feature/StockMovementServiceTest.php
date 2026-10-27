<?php

namespace Tests\Feature;

use App\Exceptions\OutOfStockException;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_out_transfer(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        set_tenant($app1);

        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);
        $warehouse2 = Warehouse::factory()->create(['tenant_id' => $app1->id]);

        $stock = app(StockMovementService::class);
        $stock->in($product1, $warehouse1, 10);
        $stock->out($product1, $warehouse1, 3);
        $stock->transfer($product1, $warehouse1, $warehouse2, 4);

        $this->assertEquals(3, $product1->stockLevels()->whereBelongsTo($warehouse1)->value('quantity'));
        $this->assertEquals(4, $product1->stockLevels()->whereBelongsTo($warehouse2)->value('quantity'));
    }

    public function test_no_negative_stock(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        set_tenant($app1);

        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);

        $stock = app(StockMovementService::class);
        $stock->in($product1, $warehouse1, 2);

        $this->expectException(OutOfStockException::class);
        $stock->out($product1, $warehouse1, 5);
    }
}
