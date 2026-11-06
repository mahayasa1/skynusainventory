<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement([StockMovement::TYPE_IN, StockMovement::TYPE_OUT]),
            'quantity' => fake()->numberBetween(1, 25),
            'note' => fake()->optional()->sentence(),
            'moved_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn (): array => ['type' => StockMovement::TYPE_IN]);
    }

    public function outgoing(): static
    {
        return $this->state(fn (): array => ['type' => StockMovement::TYPE_OUT]);
    }
}
