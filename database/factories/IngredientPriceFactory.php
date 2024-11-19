<?php

namespace Database\Factories;

use Kami\RecipeUtils\UnitConverter\Units;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\IngredientPrice>
 */
class IngredientPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'price_category_id' => \Kami\Cocktail\Models\PriceCategory::factory(),
            'ingredient_id' => \Kami\Cocktail\Models\Ingredient::factory(),
            'price' => fake()->randomNumber(2) * 100,
            'amount' => fake()->randomeLement([125, 250, 325, 500, 700, 750, 1000]),
            'units' => fake()->randomElement(Units::class),
            'description' => fake()->optional()->text(),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->optional()->dateTime(),
        ];
    }
}
