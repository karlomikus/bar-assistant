<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\CocktailFavorite>
 */
class CocktailFavoriteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'cocktail_id' => \Kami\Cocktail\Models\Cocktail::factory(),
            'bar_membership_id' => \Kami\Cocktail\Models\BarMembership::factory(),
        ];
    }
}
