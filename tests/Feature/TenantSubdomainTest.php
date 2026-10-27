<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSubdomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_subdomain(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        $app2 = Tenant::factory()->create(['subdomain' => 'app2']);

        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $product2 = Product::factory()->create(['tenant_id' => $app2->id, 'sku' => 'product2']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);
        $warehouse2 = Warehouse::factory()->create(['tenant_id' => $app2->id]);

        set_tenant($app1);
        app(StockMovementService::class)->in($product1, $warehouse1, 10);

        set_tenant($app2);
        app(StockMovementService::class)->in($product2, $warehouse2, 20);

        $this->getJson('/api/stock-levels', ['X-Subdomain' => 'app1'])
            ->assertOk()
            ->assertJsonPath('data.0.quantity', 10)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/stock-levels', ['X-Subdomain' => 'app2'])
            ->assertOk()
            ->assertJsonPath('data.0.quantity', 20)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/products/product1/history', ['X-Subdomain' => 'app2'])
            ->assertNotFound();

        $this->getJson('/api/stock-levels')->assertBadRequest();
    }
}
