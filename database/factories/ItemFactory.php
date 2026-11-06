<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([Item::TYPE_TOOL, Item::TYPE_INGREDIENT]);

        return [
            'code' => strtoupper(Str::random(3)).'-'.fake()->unique()->numberBetween(100, 999),
            'name' => ucfirst(fake()->words(2, true)),
            'type' => $type,
            'unit' => $type === Item::TYPE_TOOL ? 'pcs' : fake()->randomElement(['kg', 'liter', 'box']),
            'stock' => fake()->numberBetween(0, 80),
            'minimum_stock' => fake()->numberBetween(3, 15),
            'location' => fake()->randomElement(['Warehouse A', 'Warehouse B', 'Kitchen Store']),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function tool(): static
    {
        return $this->state(fn (): array => [
            'type' => Item::TYPE_TOOL,
            'unit' => 'pcs',
        ]);
    }

    public function ingredient(): static
    {
        return $this->state(fn (): array => [
            'type' => Item::TYPE_INGREDIENT,
            'unit' => 'kg',
        ]);
    }
}
