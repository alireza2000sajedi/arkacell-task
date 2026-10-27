<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function find(int $id): ?Product
    {
        return Product::query()->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return Product::query()->where('sku', $sku)->first();
    }

    public function ids(Tenant $tenant, int $limit): array
    {
        return $tenant->products()
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    public function count(Tenant $tenant): int
    {
        return $tenant->products()->count();
    }

    public function insertBatch(array $rows): void
    {
        DB::table('products')->insert($rows);
    }
}
