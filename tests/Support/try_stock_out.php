<?php

use App\Exceptions\OutOfStockException;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$tenantId = (int) ($argv[1] ?? 0);
$productId = (int) ($argv[2] ?? 0);
$warehouseId = (int) ($argv[3] ?? 0);

set_tenant($tenantId);

try {
    $product = Product::query()->findOrFail($productId);
    $warehouse = Warehouse::query()->findOrFail($warehouseId);

    $app->make(StockMovementService::class)->out($product, $warehouse, 1);

    exit(0);
} catch (OutOfStockException) {
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(2);
}
