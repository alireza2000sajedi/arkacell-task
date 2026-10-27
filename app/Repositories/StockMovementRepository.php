<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementRepository
{
    public function clear(Product $product, Warehouse $warehouse): void
    {
        $product->stockMovements()->whereBelongsTo($warehouse)->delete();
    }

    public function history(Product $product, int $perPage = 15): LengthAwarePaginator
    {
        return $product->stockMovements()
            ->with(['product', 'warehouse'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
