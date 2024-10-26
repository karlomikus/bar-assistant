<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\BarIngredient>
 */
class BarIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
        ];
    }
}
