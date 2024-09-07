<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\Ingredient>
 */
class IngredientFactory extends Factory
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
            'slug' => fake()->slug(),
            'origin' => fake()->country(),
            'description' => fake()->paragraph(),
            'color' => fake()->hexColor(),
            'strength' => fake()->randomFloat(2, 0, 100),
            'ingredient_category_id' => \Kami\Cocktail\Models\IngredientCategory::factory(),
            'created_user_id' => \Kami\Cocktail\Models\User::factory(),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->dateTime(),
        ];
    }
}
