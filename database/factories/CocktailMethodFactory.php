<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\CocktailMethod>
 */
class CocktailMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'dilution_percentage' => fake()->numberBetween(0, 50),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
        ];
    }
}
