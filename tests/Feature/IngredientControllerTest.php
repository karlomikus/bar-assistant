<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IngredientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Spectator::using('open-api-spec.yml');

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_list_ingredients_response()
    {
        Ingredient::factory()->count(55)->create();

        $response = $this->getJson('/api/ingredients');

        $response->assertStatus(200);
        $response->assertJsonCount(50, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 2);
        $response->assertJsonPath('meta.per_page', 50);
        $response->assertJsonPath('meta.total', 55);

        $response = $this->getJson('/api/ingredients?page=2');
        $response->assertJsonPath('meta.current_page', 2);

        $response = $this->getJson('/api/ingredients?per_page=5');
        $response->assertJsonPath('meta.last_page', 11);
    }

    public function test_list_ingredients_response_filters()
    {
        $user = User::factory()->create();
        $ingCat = IngredientCategory::factory()->create();
        Ingredient::factory()->createMany([
            ['name' => 'Whiskey', 'origin' => 'America', 'strength' => 35.5],
            ['name' => 'XXXX', 'strength' => 0],
            ['name' => 'Test', 'user_id' => $user->id, 'strength' => 40],
            ['name' => 'Test 2', 'ingredient_category_id' => $ingCat->id, 'strength' => 0],
        ]);

        $response = $this->getJson('/api/ingredients');

        $response = $this->getJson('/api/ingredients?filter[name]=whi');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[name]=whi,xx');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/ingredients?filter[user_id]=' . $user->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[category_id]=' . $ingCat->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[category_id]=' . $ingCat->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[origin]=america');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[strength_min]=30');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/ingredients?filter[strength_max]=39');
        $response->assertJsonCount(3, 'data');
        $response = $this->getJson('/api/ingredients?filter[on_shelf]=true');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/ingredients?filter[on_shopping_list]=true');
        $response->assertJsonCount(0, 'data');
    }

    public function test_list_ingredients_response_filter_by_category()
    {
        Ingredient::factory()->count(5)->create();
        $ingCat = IngredientCategory::factory()->create();
        Ingredient::factory()->count(2)->create([
            'ingredient_category_id' => $ingCat->id,
        ]);

        $response = $this->getJson('/api/ingredients?filter[category_id]=' . $ingCat->id);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        $response->assertValidResponse();
    }

    public function test_list_ingredients_response_filter_by_shopping_list()
    {
        Ingredient::factory()->count(5)->create();

        $response = $this->getJson('/api/ingredients?filter[on_shopping_list]=true');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        $response->assertValidResponse();
    }

    public function test_list_ingredients_response_filter_by_shelf()
    {
        Ingredient::factory()->count(5)->create();

        $response = $this->getJson('/api/ingredients?filter[on_shelf]=true');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        $response->assertValidResponse();
    }

    public function test_ingredient_show_response()
    {
        $ingredient = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test',
                'origin' => 'Croatia',
                'color' => '#fff',
            ])
            ->create();

        Ingredient::factory()
            ->state([
                'name' => 'Child ingredient',
                'strength' => 45.5,
                'parent_ingredient_id' => $ingredient->id
            ])
            ->create();

        Cocktail::factory()
            ->has(CocktailIngredient::factory()->state([
                'ingredient_id' => $ingredient->id,
            ]), 'ingredients')
            ->create([
                'name' => 'A cocktail name',
            ]);

        $response = $this->getJson('/api/ingredients/1');

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonPath('data.slug', 'test-ingredient');
        $response->assertJsonPath('data.name', 'Test ingredient');
        $response->assertJsonPath('data.strength', 45.5);
        $response->assertJsonPath('data.description', 'Test');
        $response->assertJsonPath('data.origin', 'Croatia');
        $response->assertJsonPath('data.main_image_id', null);
        $response->assertJsonPath('data.images', []);
        $response->assertJsonPath('data.ingredient_category_id', 1);
        $response->assertJsonPath('data.parent_ingredient.id', null);
        $response->assertJsonPath('data.color', '#fff');
        $response->assertJsonCount(1, 'data.cocktails');
        $response->assertJsonCount(1, 'data.varieties');

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
