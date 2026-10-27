<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Tenant;

class InventoryReconcileService
{
    public function findMismatches(Tenant $tenant): array
    {
        set_tenant($tenant);

        $fromHistory = $this->stockFromMovements($tenant);
        $fromLevels = $this->stockFromLevels($tenant);

        $keys = array_unique(array_merge(array_keys($fromHistory), array_keys($fromLevels)));
        sort($keys);

        $mismatches = [];

        foreach ($keys as $key) {
            $expected = $fromHistory[$key] ?? 0;
            $actual = $fromLevels[$key] ?? 0;

            if ($expected === $actual) {
                continue;
            }

            [$productId, $warehouseId] = array_map('intval', explode(':', $key, 2));

            $mismatches[] = [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'expected' => $expected,
                'actual' => $actual,
                'diff' => $actual - $expected,
            ];
        }

        return $mismatches;
    }

    private function stockFromMovements(Tenant $tenant): array
    {
        $stock = [];

        $tenant->stockMovements()
            ->orderBy('id')
            ->chunkById(500, function ($movements) use (&$stock) {
                foreach ($movements as $movement) {
                    $qty = (int) $movement->quantity;
                    $key = $movement->product_id.':'.$movement->warehouse_id;

                    if ($movement->type === StockMovementType::IN) {
                        $stock[$key] = ($stock[$key] ?? 0) + $qty;
                    } elseif ($movement->type === StockMovementType::OUT) {
                        $stock[$key] = ($stock[$key] ?? 0) - $qty;
                    } elseif ($movement->type === StockMovementType::TRANSFER) {
                        $fromId = (int) ($movement->meta['from_warehouse_id'] ?? $movement->warehouse_id);
                        $toId = (int) ($movement->meta['to_warehouse_id'] ?? 0);
                        $fromKey = $movement->product_id.':'.$fromId;

                        $stock[$fromKey] = ($stock[$fromKey] ?? 0) - $qty;

                        if ($toId > 0) {
                            $toKey = $movement->product_id.':'.$toId;
                            $stock[$toKey] = ($stock[$toKey] ?? 0) + $qty;
                        }
                    }
                }
            });

        return $stock;
    }

    private function stockFromLevels(Tenant $tenant): array
    {
        $stock = [];

        $tenant->stockLevels()
            ->orderBy('id')
            ->chunkById(500, function ($levels) use (&$stock) {
                foreach ($levels as $level) {
                    $key = $level->product_id.':'.$level->warehouse_id;
                    $stock[$key] = (int) $level->quantity;
                }
            });

        return $stock;
    }
}
