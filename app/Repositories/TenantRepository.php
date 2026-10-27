<?php

namespace App\Repositories;

use App\Models\Tenant;

class TenantRepository
{
    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return Tenant::query()->where('subdomain', $subdomain)->first();
    }

    public function find(string $idOrSubdomain): ?Tenant
    {
        if (ctype_digit($idOrSubdomain)) {
            return Tenant::query()->find((int) $idOrSubdomain);
        }

        return $this->findBySubdomain($idOrSubdomain);
    }

    public function firstOrCreateBySubdomain(string $subdomain, string $name): Tenant
    {
        return Tenant::query()->firstOrCreate(
            ['subdomain' => $subdomain],
            ['name' => $name],
        );
    }
}
