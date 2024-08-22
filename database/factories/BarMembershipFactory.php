<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kami\Cocktail\Models\UserRoleEnum;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\BarMembership>
 */
class BarMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'is_active' => true,
            'user_role_id' => UserRoleEnum::Admin->value,
            'user_id' => \Kami\Cocktail\Models\User::factory(),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
        ];
    }
}
