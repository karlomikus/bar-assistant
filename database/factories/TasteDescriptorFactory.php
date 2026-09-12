<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\TasteDescriptor>
 */
class TasteDescriptorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'normalized_name' => mb_strtolower($name),
            'bar_id' => \Kami\Cocktail\Models\Bar::factory(),
        ];
    }
}
