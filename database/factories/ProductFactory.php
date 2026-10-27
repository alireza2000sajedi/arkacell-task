<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('#####')),
            'name' => fake()->words(3, true),
            'unit_price' => fake()->randomFloat(0, 1000000, 10000000),
        ];
    }
}
