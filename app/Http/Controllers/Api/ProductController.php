<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use Ars\Responder\Facades\Responder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductRepository $products, private StockMovementRepository $movements)
    {
    }

    public function history(Request $request, string $sku): JsonResponse
    {
        $product = $this->products->findBySku($sku);

        if (!$product) {
            return Responder::notFound();
        }

        $page = $this->movements->history(
            $product,
            $request->integer('per_page', 15),
        );

        $page->through(fn($movement) => (new StockMovementResource($movement))->resolve());

        return Responder::setStatusCode(200)->paginate($page);
    }
}
