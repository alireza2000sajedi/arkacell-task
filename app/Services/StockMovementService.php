<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\OutOfStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Repositories\StockLevelRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockMovementService
{
    public function __construct(private StockLevelRepository $stockLevels)
    {
    }

    public function in(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        ?string $reference = null,
        ?array $meta = null
    ): StockMovement {
        return $this->apply(StockMovementType::IN, $product, $warehouse, $quantity, $reference, $meta);
    }

    private function apply(
        StockMovementType $type,
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        ?string $reference,
        ?array $meta
    ): StockMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException(__('stock.quantity_invalid'));
        }

        return DB::transaction(function () use (
            $type,
            $product,
            $warehouse,
            $quantity,
            $reference,
            $meta
        ) {
            $level = $this->stockLevels->getLocked($product, $warehouse);

            if ($type === StockMovementType::OUT) {
                if ($level->quantity < $quantity) {
                    throw new OutOfStockException(
                        $product->id,
                        $warehouse->id,
                        $quantity,
                        $level->quantity,
                    );
                }

                $level->decrement('quantity', $quantity);
            } else {
                $level->increment('quantity', $quantity);
            }

            return $product->stockMovements()->create([
                'tenant_id' => $product->tenant_id,
                'warehouse_id' => $warehouse->id,
                'type' => $type,
                'quantity' => $quantity,
                'reference' => $reference,
                'meta' => $meta,
            ]);
        });
    }

    public function out(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        ?string $reference = null,
        ?array $meta = null
    ): StockMovement {
        return $this->apply(StockMovementType::OUT, $product, $warehouse, $quantity, $reference, $meta);
    }

    // Smaller warehouse id is locked first so two reverse transfers cannot deadlock.

    public function transfer(
        Product $product,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        int $quantity,
        ?string $reference = null,
        ?array $meta = null
    ): StockMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException(__('stock.quantity_invalid'));
        }

        if ($fromWarehouse->is($toWarehouse)) {
            throw new InvalidArgumentException(__('stock.transfer_same_warehouse'));
        }

        return DB::transaction(function () use (
            $product,
            $fromWarehouse,
            $toWarehouse,
            $quantity,
            $reference,
            $meta
        ) {
            [$from, $to] = $this->lockTransferLevels($product, $fromWarehouse, $toWarehouse);

            if ($from->quantity < $quantity) {
                throw new OutOfStockException(
                    $product->id,
                    $fromWarehouse->id,
                    $quantity,
                    $from->quantity,
                );
            }

            $from->decrement('quantity', $quantity);
            $to->increment('quantity', $quantity);

            return $product->stockMovements()->create([
                'tenant_id' => $product->tenant_id,
                'warehouse_id' => $fromWarehouse->id,
                'type' => StockMovementType::TRANSFER,
                'quantity' => $quantity,
                'reference' => $reference,
                'meta' => array_merge($meta ?? [], [
                    'from_warehouse_id' => $fromWarehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                ]),
            ]);
        });
    }

    private function lockTransferLevels(Product $product, Warehouse $fromWarehouse, Warehouse $toWarehouse): array
    {
        $warehouses = [$fromWarehouse, $toWarehouse];

        usort($warehouses, fn(Warehouse $a, Warehouse $b) => $a->id <=> $b->id);

        $levels = [];

        foreach ($warehouses as $warehouse) {
            $levels[$warehouse->id] = $this->stockLevels->getLocked($product, $warehouse);
        }

        return [
            $levels[$fromWarehouse->id],
            $levels[$toWarehouse->id],
        ];
    }
}
