<?php

namespace App\Models\Utilities;

use App\Scopes\TenantScope;

trait AddGlobalScopeTenant
{
    protected static function bootAddGlobalScopeTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
