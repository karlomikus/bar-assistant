<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\UserShoppingList>
 */
class UserShoppingListFactory extends Factory
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
            'bar_membership_id' => \Kami\Cocktail\Models\BarMembership::factory(),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
