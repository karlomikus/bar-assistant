<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\CocktailIngredient>
 */
class CocktailIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'amount' => 60,
            'units' => 'ml',
            'ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
        ];
    }
}
