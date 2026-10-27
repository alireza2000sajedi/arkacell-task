<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseRepository
{
    public function find(int $id): ?Warehouse
    {
        return Warehouse::query()->find($id);
    }

    public function ids(Tenant $tenant, int $limit): array
    {
        return $tenant->warehouses()
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    public function count(Tenant $tenant): int
    {
        return $tenant->warehouses()->count();
    }

    public function insertBatch(array $rows): void
    {
        DB::table('warehouses')->insert($rows);
    }
}
