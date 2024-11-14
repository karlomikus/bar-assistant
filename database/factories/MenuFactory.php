<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\Menu>
 */
class MenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
            'is_enabled' => fake()->boolean(),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->optional()->dateTime(),
        ];
    }
}
