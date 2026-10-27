<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Tests\TestCase;

class TenantSubdomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_subdomain(): void
    {
        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        $app2 = Tenant::factory()->create(['subdomain' => 'app2']);

        Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        Product::factory()->create(['tenant_id' => $app2->id, 'sku' => 'product2']);

        $this->getJson('/api/products', ['X-Subdomain' => 'app1'])
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'product1')
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/products')->assertBadRequest();
    }
}
