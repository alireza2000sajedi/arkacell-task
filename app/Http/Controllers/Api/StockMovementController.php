<?php

namespace App\Http\Controllers\Api;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Repositories\ProductRepository;
use App\Repositories\WarehouseRepository;
use App\Services\StockMovementService;
use Ars\Responder\Facades\Responder;
use Illuminate\Http\JsonResponse;

class StockMovementController extends Controller
{
    public function __construct(
        private StockMovementService $stock,
        private ProductRepository $products,
        private WarehouseRepository $warehouses
    ) {
    }

    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        $type = StockMovementType::from($request->string('type')->toString());
        $product = $this->products->find($request->integer('product_id'));
        $warehouse = $this->warehouses->find($request->integer('warehouse_id'));
        $qty = $request->integer('quantity');
        $reference = $request->input('reference');
        $meta = $request->input('meta');

        $movement = match ($type) {
            StockMovementType::IN => $this->stock->in($product, $warehouse, $qty, $reference, $meta),
            StockMovementType::OUT => $this->stock->out($product, $warehouse, $qty, $reference, $meta),
            StockMovementType::TRANSFER => $this->stock->transfer(
                $product,
                $warehouse,
                $this->warehouses->find($request->integer('to_warehouse_id')),
                $qty,
                $reference,
                $meta,
            ),
        };

        $movement->load(['product', 'warehouse']);

        return Responder::created((new StockMovementResource($movement))->resolve());
    }
}
