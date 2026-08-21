<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kami\Cocktail\Models\Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'copyright' => fake()->paragraph(),
            'file_path' => fake()->filePath(),
            'file_extension' => fake()->fileExtension(),
            'disk' => 'uploads',
            'storage_origin' => 'owned',
            'created_user_id' => \Kami\Cocktail\Models\User::factory(),
        ];
    }
}
