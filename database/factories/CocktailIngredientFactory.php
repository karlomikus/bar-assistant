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
            'amount' => fake()->numberBetween(1, 60),
            'amount_max' => fake()->optional()->numberBetween(1, 60),
            'sort' => 1,
            'optional' => fake()->boolean(),
            'note' => fake()->sentence(),
            'units' => fake()->randomElement(['ml', 'cl', 'oz', 'dashes', 'drops', 'tablespoons', 'teaspoons', 'cups', 'pints', 'quarts']),
            'cocktail_id' => \Kami\Cocktail\Models\Cocktail::factory(),
            'ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
        ];
    }
}
