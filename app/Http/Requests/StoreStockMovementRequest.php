<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id')->where('tenant_id', tenant_id()),
            ],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists(Warehouse::class, 'id')->where('tenant_id', tenant_id()),
            ],
            'to_warehouse_id' => [
                'required_if:type,transfer',
                'nullable',
                'integer',
                'different:warehouse_id',
                Rule::exists(Warehouse::class, 'id')->where('tenant_id', tenant_id()),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
