<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\ComplexIngredient>
 */
class ComplexIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'main_ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
            'ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
        ];
    }
}
