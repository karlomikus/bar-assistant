<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\MenuIngredient>
 */
class MenuIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'menu_category_id' => \Kami\Cocktail\Models\MenuCategory::factory(),
            'sort' => 1,
            'ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
            'price' => fake()->randomNumber(2),
            'currency' => fake()->currencyCode(),
        ];
    }
}
