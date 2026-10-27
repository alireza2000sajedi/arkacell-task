<?php

namespace Tests\Performance;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group performance
 */
class StockLevelPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected $connectionsToTransact = [];

    private const ROWS = 1_000_000;

    private const PRODUCTS = 1000;

    private const WAREHOUSES = 1000;

    public function test_stock_levels_stay_under_200ms_with_one_million_rows(): void
    {
        Artisan::call('migrate:fresh');

        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        set_tenant($app1);

        $this->seedProducts($app1);
        $this->seedWarehouses($app1);

        $productIds = $app1->products()->orderBy('id')->limit(self::PRODUCTS)->pluck('id')->all();
        $warehouseIds = $app1->warehouses()->orderBy('id')->limit(self::WAREHOUSES)->pluck('id')->all();

        $this->seedStockLevels($app1, $productIds, $warehouseIds);

        $this->assertSame(self::ROWS, StockLevel::query()->where('tenant_id', $app1->id)->count());

        $product1 = Product::query()->where('tenant_id', $app1->id)->orderBy('id')->firstOrFail();
        $warehouse1 = Warehouse::query()->where('tenant_id', $app1->id)->orderBy('id')->firstOrFail();

        $cases = [
            'product_id' => '/api/stock-levels?product_id='.$product1->id.'&per_page=15',
            'warehouse_id' => '/api/stock-levels?warehouse_id='.$warehouse1->id.'&per_page=15',
            'both' => '/api/stock-levels?product_id='.$product1->id.'&warehouse_id='.$warehouse1->id.'&per_page=15',
        ];

        // Warm one request so schema/connection cost is not counted.
        $this->getJson($cases['product_id'], ['X-Subdomain' => 'app1'])->assertOk();

        foreach ($cases as $label => $path) {
            $start = hrtime(true);
            $response = $this->getJson($path, ['X-Subdomain' => 'app1']);
            $ms = (hrtime(true) - $start) / 1e6;

            $response->assertOk()->assertJsonStructure(['data']);

            $this->assertLessThan(
                200,
                $ms,
                "GET stock-levels ({$label}) took {$ms}ms; target is < 200ms with ".self::ROWS.' rows.',
            );
        }
    }

    private function seedProducts(Tenant $tenant): void
    {
        $now = now()->toDateTimeString();
        $batch = [];

        for ($i = 1; $i <= self::PRODUCTS; $i++) {
            $batch[] = array_merge(
                Product::factory()->make([
                    'tenant_id' => $tenant->id,
                    'sku' => sprintf('PERF-%06d', $i),
                    'name' => 'Perf Product '.$i,
                ])->getAttributes(),
                ['created_at' => $now, 'updated_at' => $now],
            );

            if (count($batch) >= 500) {
                DB::table('products')->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('products')->insert($batch);
        }
    }

    private function seedWarehouses(Tenant $tenant): void
    {
        $now = now()->toDateTimeString();
        $batch = [];

        for ($i = 1; $i <= self::WAREHOUSES; $i++) {
            $batch[] = array_merge(
                Warehouse::factory()->make([
                    'tenant_id' => $tenant->id,
                    'name' => 'Perf WH '.$i,
                    'location' => 'Loc '.$i,
                ])->getAttributes(),
                ['created_at' => $now, 'updated_at' => $now],
            );

            if (count($batch) >= 500) {
                DB::table('warehouses')->insert($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('warehouses')->insert($batch);
        }
    }

    private function seedStockLevels(Tenant $tenant, array $productIds, array $warehouseIds): void
    {
        $now = now()->toDateTimeString();
        $batch = [];
        $inserted = 0;

        foreach ($productIds as $productId) {
            foreach ($warehouseIds as $warehouseId) {
                if ($inserted >= self::ROWS) {
                    break 2;
                }

                $batch[] = array_merge(
                    StockLevel::factory()->make([
                        'tenant_id' => $tenant->id,
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                    ])->getAttributes(),
                    ['updated_at' => $now],
                );

                $inserted++;

                if (count($batch) >= 2000) {
                    DB::table('stock_levels')->insert($batch);
                    $batch = [];
                }
            }
        }

        if ($batch !== []) {
            DB::table('stock_levels')->insert($batch);
        }
    }
}
