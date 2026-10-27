<?php

namespace App\Console\Commands;

use App\Repositories\TenantRepository;
use App\Services\InventoryReconcileService;
use Illuminate\Console\Command;

class InventoryReconcileCommand extends Command
{
    protected $signature = 'inventory:reconcile {tenant : Tenant id or subdomain}';

    protected $description = 'Compare stock levels with movement history';

    public function handle(
        TenantRepository $tenants,
        InventoryReconcileService $reconcile,
    ): int {
        $tenant = $tenants->find((string) $this->argument('tenant'));

        if (!$tenant) {
            $this->error('Tenant not found');

            return self::FAILURE;
        }

        $rows = $reconcile->findMismatches($tenant);

        if ($rows === []) {
            $this->info('No mismatches.');

            return self::SUCCESS;
        }

        $this->table(
            ['Product', 'Warehouse', 'Expected', 'Actual', 'Diff'],
            array_map(fn(array $row) => [
                $row['product_id'],
                $row['warehouse_id'],
                $row['expected'],
                $row['actual'],
                $row['diff'],
            ], $rows),
        );

        $this->warn(count($rows).' mismatch(es).');

        return self::SUCCESS;
    }
}
