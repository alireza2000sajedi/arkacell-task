<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function exists(int $id): bool
    {
        return Product::whereKey($id)->exists();
    }

    public function findBySku(string $sku): ?Product
    {
        return Product::query()->where('sku', $sku)->first();
    }

    public function all(): Collection
    {
        return Product::query()->orderBy('id')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()->orderBy('id')->paginate($perPage);
    }
}
