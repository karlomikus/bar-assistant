<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_list_ingredients_response()
    {
        Ingredient::factory()->count(5)->create();

        $response = $this->getJson('/api/ingredients');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        $response->assertValidResponse();
    }

    public function test_ingredient_show_response()
    {
        Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test'
            ])
            ->create();

        $response = $this->getJson('/api/ingredients/1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonPath('data.slug', 'test-ingredient');
        $response->assertJsonPath('data.name', 'Test ingredient');
        $response->assertJsonPath('data.strength', 45.5);
        $response->assertJsonPath('data.description', 'Test');

        $response->assertValidResponse();
    }

    public function test_ingredient_show_not_found_response()
    {
        $response = $this->getJson('/api/ingredients/404');

        $response->assertStatus(404);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('message')
                ->etc()
        );

        $response->assertValidResponse(404);
    }

    public function test_ingredient_store_response()
    {
        $ingCat = IngredientCategory::factory()->create();

        $response = $this->postJson('/api/ingredients', [
            'name' => "Ingredient name",
            'strength' => 12.2,
            'description' => "Description text",
            'origin' => "Worldwide",
            'color' => "#000000",
            'ingredient_category_id' => $ingCat->id,
            'parent_ingredient_id' => null
        ]);

        $response->assertStatus(201);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Ingredient name')
                ->where('data.strength', 12.2)
                ->where('data.description', 'Description text')
                ->where('data.origin', 'Worldwide')
                ->where('data.color', '#000000')
                ->where('data.ingredient_category_id', $ingCat->id)
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse(201);
    }

    public function test_ingredient_store_fails_validation_response()
    {
        $response = $this->postJson('/api/ingredients', [
            'strength' => 12.2,
        ]);

        $response->assertStatus(422);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('message')
                ->has('errors')
                ->etc()
        );

        $response->assertValidResponse(422);
    }

    public function test_ingredient_update_response()
    {
        $ing = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test'
            ])
            ->create();

        $response = $this->putJson('/api/ingredients/' . $ing->id, [
            'name' => "Ingredient name",
            'strength' => 12.2,
            'description' => "Description text",
            'origin' => "Worldwide",
            'color' => "#000000",
            'ingredient_category_id' => 1,
            'parent_ingredient_id' => null
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.name', 'Ingredient name')
                ->where('data.strength', 12.2)
                ->where('data.description', 'Description text')
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse(200);
    }

    public function test_ingredient_update_fails_validation_response()
    {
        $ing = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test'
            ])
            ->create();

        $response = $this->putJson('/api/ingredients/' . $ing->id, [
            'strength' => 12.2,
        ]);

        $response->assertStatus(422);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('message')
                ->has('errors')
                ->etc()
        );

        $response->assertValidResponse(422);
    }

    public function test_ingredient_delete_response()
    {
        $ing = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test'
            ])
            ->create();

        $response = $this->deleteJson('/api/ingredients/' . $ing->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('ingredients', ['id' => $ing->id]);
    }
}
