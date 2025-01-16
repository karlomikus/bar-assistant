<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\UserOAuthAccount>
 */
class UserOAuthAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => \Kami\Cocktail\Models\User::factory(),
            'provider_id' => fake()->name(),
            'provider_user_id' => fake()->randomNumber(5),
        ];
    }
}
