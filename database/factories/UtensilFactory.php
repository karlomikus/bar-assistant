<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\Utensil>
 */
class UtensilFactory extends Factory
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
            'description' => fake()->paragraph(),
            'created_user_id' => \Kami\Cocktail\Models\User::factory(),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
        ];
    }
}
