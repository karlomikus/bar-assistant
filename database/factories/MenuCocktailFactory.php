<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\MenuCocktail>
 */
class MenuCocktailFactory extends Factory
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
            'cocktail_id' => \Kami\Cocktail\Models\Cocktail::factory(),
            'price' => fake()->randomNumber(2),
            'currency' => fake()->currencyCode(),
        ];
    }
}
