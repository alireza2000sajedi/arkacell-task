<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class StockConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected $connectionsToTransact = [];

    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh');
        parent::tearDown();
    }

    public function test_one_hundred_concurrent_outs_against_stock_of_fifty(): void
    {
        Artisan::call('migrate:fresh');

        $app1 = Tenant::factory()->create(['subdomain' => 'app1']);
        set_tenant($app1);

        $product1 = Product::factory()->create(['tenant_id' => $app1->id, 'sku' => 'product1']);
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $app1->id]);

        app(StockMovementService::class)->in($product1, $warehouse1, 50);

        // Workers must use the same test DB as PHPUnit (not .env), otherwise
        // they hit another database and all 100 outs can "succeed".
        $dbEnv = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => (string) config('database.connections.mysql.host'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'DB_DATABASE' => (string) config('database.connections.mysql.database'),
            'DB_USERNAME' => (string) config('database.connections.mysql.username'),
            'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
        ];

        $results = Process::pool(function ($pool) use ($app1, $product1, $warehouse1, $dbEnv) {
            for ($i = 0; $i < 100; $i++) {
                $pool->path(base_path())
                    ->timeout(60)
                    ->env($dbEnv)
                    ->command([
                        PHP_BINARY,
                        'tests/Support/try_stock_out.php',
                        (string) $app1->id,
                        (string) $product1->id,
                        (string) $warehouse1->id,
                    ]);
            }
        })->start()->wait();

        $ok = 0;
        $failed = 0;

        foreach ($results as $result) {
            if ($result->exitCode() === 0) {
                $ok++;
            } else {
                $failed++;
            }
        }

        $left = (int) $product1->stockLevels()->whereBelongsTo($warehouse1)->value('quantity');
        $outs = $product1->stockMovements()
            ->whereBelongsTo($warehouse1)
            ->where('type', StockMovementType::OUT)
            ->count();

        $this->assertSame(50, $ok);
        $this->assertSame(50, $failed);
        $this->assertSame(50, $outs);
        $this->assertSame(0, $left);
    }
}
