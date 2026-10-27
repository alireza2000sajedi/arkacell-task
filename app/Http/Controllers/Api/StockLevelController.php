<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexStockLevelRequest;
use App\Http\Resources\StockLevelResource;
use App\Repositories\ProductRepository;
use App\Repositories\StockLevelRepository;
use App\Repositories\WarehouseRepository;
use Ars\Responder\Facades\Responder;
use Illuminate\Http\JsonResponse;

class StockLevelController extends Controller
{
    public function __construct(
        private StockLevelRepository $levels,
        private ProductRepository $products,
        private WarehouseRepository $warehouses
    ) {
    }

    public function index(IndexStockLevelRequest $request): JsonResponse
    {
        $product = $request->filled('product_id')
            ? $this->products->find($request->integer('product_id'))
            : null;

        $warehouse = $request->filled('warehouse_id')
            ? $this->warehouses->find($request->integer('warehouse_id'))
            : null;

        $page = $this->levels->paginate(
            $product,
            $warehouse,
            $request->integer('per_page', 15),
        );

        $page->through(fn($level) => (new StockLevelResource($level))->resolve());

        return Responder::setStatusCode(200)->paginate($page);
    }
}
