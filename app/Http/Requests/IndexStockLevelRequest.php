<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexStockLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'sometimes',
                'integer',
                Rule::exists(Product::class, 'id')->where('tenant_id', tenant_id()),
            ],
            'warehouse_id' => [
                'sometimes',
                'integer',
                Rule::exists(Warehouse::class, 'id')->where('tenant_id', tenant_id()),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
