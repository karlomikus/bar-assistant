<?php

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_response()
    {
        $user = User::factory()->create();
        Ingredient::factory()
            ->count(5)
            ->forCategory()
            ->forUser()
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/api/ingredients');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_show_response()
    {
        $user = User::factory()->create();
        Ingredient::factory()
            ->forCategory()
            ->forUser()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test'
            ])
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/api/ingredients/1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonPath('data.slug', 'test-ingredient');
        $response->assertJsonPath('data.name', 'Test ingredient');
        $response->assertJsonPath('data.strength', 45.5);
        $response->assertJsonPath('data.description', 'Test');
    }
}
