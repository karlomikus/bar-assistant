<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShoppingListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');
    }

    public function test_list_ingredients_on_shopping_list_response()
    {
        $ingredients = Ingredient::factory()->count(5)->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ($ingredients as $ingredient) {
            $usl = new UserShoppingList();
            $usl->ingredient_id = $ingredient->id;
            $user->shoppingLists()->save($usl);
        }

        $response = $this->getJson('/api/shopping-list');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 5)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_add_multiple_ingredients_to_shopping_list_response()
    {
        $ingredients = Ingredient::factory()->count(3)->create();
        $this->actingAs(User::factory()->create());

        $response = $this->postJson('/api/shopping-list/batch-store', [
            'ingredient_ids' => $ingredients->pluck('id')->toArray()
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 3)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_delete_multiple_ingredients_from_shopping_list_response()
    {
        $ingredients = Ingredient::factory()->count(2)->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ($ingredients as $ingredient) {
            $usl = new UserShoppingList();
            $usl->ingredient_id = $ingredient->id;
            $user->shoppingLists()->save($usl);
        }

        $response = $this->postJson('/api/shopping-list/batch-delete', [
            'ingredient_ids' => $ingredients->pluck('id')->toArray()
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.ingredient_ids', 2)
                ->etc()
        );

        $response->assertValidResponse();
    }
}
