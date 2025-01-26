<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\CalculatorBlock>
 */
class CalculatorBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'label' => fake()->name(),
            'type' => fake()->randomElement(CalculatorBlockTypeEnum::class),
            'description' => fake()->optional()->paragraph(),
            'variable_name' => Str::slug(fake()->userName()),
            'value' => '1 + 2',
            'sort' => 1,
            'settings' => json_encode(['suffix' => 'suf', 'prefix' => 'pre']),
            'calculator_id' => \Kami\Cocktail\Models\Calculator::factory(),
        ];
    }
}
