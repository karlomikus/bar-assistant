<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShelfControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Spectator::using('open-api-spec.yml');

        $ingredients = Ingredient::factory()->count(5)->create();
        $user = User::factory()->create();

        foreach ($ingredients as $ingredient) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $ingredient->id;
            $user->shelfIngredients()->save($userIngredient);
        }

        $this->actingAs($user);
    }

    public function test_list_all_user_ingredients_response()
    {
        $response = $this->getJson('/api/shelf');

        $response->assertSuccessful();
        $response->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('data', 5)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_add_multiple_ingredients_to_shelf_response()
    {
        $newIngredients = Ingredient::factory()->count(2)->create();

        $response = $this->postJson('/api/shelf', [
            'ingredient_ids' => $newIngredients->pluck('id')->toArray()
        ]);

        $response->assertSuccessful();
        $response->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('data', 2)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_add_ingredient_to_shelf_response()
    {
        $newIngredient = Ingredient::factory()->create();

        $response = $this->postJson('/api/shelf/' . $newIngredient->id);

        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->has('data.user_id')
                ->where('data.ingredient_id', $newIngredient->id)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_delete_ingredient_from_shelf_response()
    {
        $newIngredient = Ingredient::factory()->create();

        $response = $this->deleteJson('/api/shelf/' . $newIngredient->id);

        $response->assertNoContent();

        $response->assertValidResponse();

        $this->assertDatabaseMissing('user_ingredients', ['ingredient_id' => $newIngredient->id]);
    }
}
