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
            'category_name' => fake()->name(),
            'sort' => 1,
            'menu_id' => \Kami\Cocktail\Models\Menu::factory(),
            'cocktail_id' => \Kami\Cocktail\Models\Cocktail::factory(),
            'price' => fake()->randomNumber(2),
            'currency' => fake()->currencyCode(),
        ];
    }
}
