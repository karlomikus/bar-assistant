<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\PriceCategory>
 */
class PriceCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'currency' => fake()->countryISOAlpha3(),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
        ];
    }
}
