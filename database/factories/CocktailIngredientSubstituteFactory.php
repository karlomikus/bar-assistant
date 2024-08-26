<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\CocktailIngredientSubstitute>
 */
class CocktailIngredientSubstituteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'amount' => fake()->optional()->numberBetween(1, 60),
            'amount_max' => fake()->optional()->numberBetween(1, 60),
            'units' => fake()->optional()->randomElement(['ml', 'cl', 'oz', 'dashes', 'drops', 'tablespoons', 'teaspoons', 'cups', 'pints', 'quarts']),
            'cocktail_ingredient_id' => \Kami\Cocktail\Models\CocktailIngredient::factory(),
            'ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
        ];
    }
}
