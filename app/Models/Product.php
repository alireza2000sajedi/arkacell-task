<?php

namespace App\Models;

use App\Models\Utilities\AddGlobalScopeTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, AddGlobalScopeTenant;

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:0',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }
}
