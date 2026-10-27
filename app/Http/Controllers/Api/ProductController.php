<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository;
use Ars\Responder\Facades\Responder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = $this->products->paginate(
            $request->integer('per_page', 15),
        );

        $page->through(fn ($product) => (new ProductResource($product))->resolve());

        return Responder::setStatusCode(200)->paginate($page);
    }
}
