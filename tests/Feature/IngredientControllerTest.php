<?php

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_ingredients_response()
    {
        $user = User::factory()->create();
        Ingredient::factory()
            ->count(5)
            ->create();

        $response = $this->actingAs($user)
            ->getJson('/api/ingredients');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_ingredient_show_response()
    {
        $user = User::factory()->create();
        Ingredient::factory()
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

    public function test_ingredient_store_response()
    {
        $user = User::factory()->create();
        $ingCat = IngredientCategory::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/ingredients', [
                'name' => "Ingredient name",
                'strength' => 12.2,
                'description' => "Description text",
                'origin' => "Worldwide",
                'color' => "#000000",
                'ingredient_category_id' => $ingCat->id,
                'parent_ingredient_id' => null
            ]);

        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data.id')
                ->where('data.name', 'Ingredient name')
                ->where('data.strength', 12.2)
                ->where('data.description', 'Description text')
                ->where('data.origin', 'Worldwide')
                ->where('data.color', '#000000')
                ->where('data.ingredient_category_id', $ingCat->id)
                ->where('data.parent_ingredient_id', null)
                ->etc()
        );
    }

    public function test_ingredient_update_response()
    {
        $user = User::factory()->create();
        $ing = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test'
            ])
            ->create();

        $response = $this->actingAs($user)
            ->putJson('/api/ingredients/' . $ing->id, [
                'name' => "Ingredient name",
                'strength' => 12.2,
                'description' => "Description text",
                'origin' => "Worldwide",
                'color' => "#000000",
                'ingredient_category_id' => 1,
                'parent_ingredient_id' => null
            ]);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data.id')
                ->where('data.name', 'Ingredient name')
                ->where('data.strength', 12.2)
                ->where('data.description', 'Description text')
                ->etc()
        );
    }

    public function test_ingredient_delete_response()
    {
        $user = User::factory()->create();
        $ing = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test'
            ])
            ->create();

        $response = $this->actingAs($user)
            ->deleteJson('/api/ingredients/' . $ing->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ingredients', ['id' => $ing->id]);
    }
}
