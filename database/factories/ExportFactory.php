<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\Export>
 */
class ExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'is_done' => fake()->boolean(),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
            'created_user_id' => \Kami\Cocktail\Models\User::factory(),
            'filename' => fake()->filePath(),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->optional()->dateTime(),
        ];
    }
}
