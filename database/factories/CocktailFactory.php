<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\Cocktail>
 */
class CocktailFactory extends Factory
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
            'description' => fake()->optional()->paragraph(),
            'instructions' => fake()->paragraph(),
            'source' => fake()->optional()->url(),
            'garnish' => fake()->optional()->paragraph(),
            'cocktail_method_id' => \Kami\Cocktail\Models\CocktailMethod::factory(),
            'glass_id' => \Kami\Cocktail\Models\Glass::factory(),
            'abv' => fake()->randomFloat(2, 0, 80),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->optional()->dateTime(),
            'created_user_id' => \Kami\Cocktail\Models\User::factory(),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
        ];
    }
}
