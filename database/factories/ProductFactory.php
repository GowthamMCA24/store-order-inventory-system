<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->bothify('PRD-####'),
            'price' => fake()->randomFloat(2, 10, 1000),
            'tax_percentage' => fake()->randomElement([0, 5, 12, 18]),
            'stock' => fake()->numberBetween(0, 100),
        ];
    }
}
