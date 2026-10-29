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
        $cost = fake()->randomFloat(2, 500, 40000);

        return [
            'name' => ucfirst(fake()->words(3, true)),
            'sku' => fake()->unique()->bothify('BZ-#####'),
            'barcode' => fake()->unique()->ean13(),
            'description' => fake()->sentence(14),
            'unit' => 'unidad',
            'cost_price' => $cost,
            'sale_price' => round($cost * fake()->randomFloat(2, 1.3, 1.9), -1),
            'tax_rate' => 21,
            'stock' => fake()->numberBetween(0, 120),
            'min_stock' => fake()->randomElement([3, 5, 10]),
            'is_active' => fake()->boolean(90),
            'is_published' => fake()->boolean(60),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock' => max(1, $attributes['min_stock'] - 1)]);
    }
}
