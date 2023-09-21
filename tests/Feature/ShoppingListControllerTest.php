<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserRoleEnum;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShoppingListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_list_ingredients_on_shopping_list_response(): void
    {
        $this->setupBar();
        $ingredients = Ingredient::factory()->count(5)->create(['bar_id' => 1]);

        foreach ($ingredients as $ingredient) {
            $usl = new UserShoppingList();
            $usl->ingredient_id = $ingredient->id;
            $usl->bar_membership_id = 1;
            $usl->save();
        }

        $response = $this->getJson('/api/shopping-list?bar_id=1');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 5)
                ->etc()
        );
    }

    public function test_add_multiple_ingredients_to_shopping_list_response(): void
    {
        $this->setupBar();
        $ingredients = Ingredient::factory()->count(3)->create(['bar_id' => 1]);

        $response = $this->postJson('/api/shopping-list/batch-store?bar_id=1', [
            'ingredient_ids' => $ingredients->pluck('id')->toArray()
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 3)
                ->etc()
        );
    }

    public function test_add_multiple_ingredients_to_shopping_list_from_another_bar_response(): void
    {
        $this->setupBar();

        Bar::factory()->create(['id' => 2]);
        DB::table('bar_memberships')->insert(['id' => 2, 'bar_id' => 2, 'user_id' => 1, 'user_role_id' => UserRoleEnum::Admin->value]);
        $ing1 = Ingredient::factory()->create(['bar_id' => 1]);
        $ing2 = Ingredient::factory()->create(['bar_id' => 2]);

        $response = $this->postJson('/api/shopping-list/batch-store?bar_id=1', [
            'ingredient_ids' => [$ing1->id, $ing2->id]
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 1)
                ->where('data.0.ingredient_id', $ing1->id)
                ->etc()
        );
    }

    public function test_delete_multiple_ingredients_from_shopping_list_response(): void
    {
        $this->setupBar();
        $ingredients = Ingredient::factory()->count(2)->create(['bar_id' => 1]);

        foreach ($ingredients as $ingredient) {
            $usl = new UserShoppingList();
            $usl->ingredient_id = $ingredient->id;
            $usl->bar_membership_id = 1;
            $usl->save();
        }

        $response = $this->postJson('/api/shopping-list/batch-delete?bar_id=1', [
            'ingredient_ids' => $ingredients->pluck('id')->toArray()
        ]);

        $response->assertNoContent();

        foreach ($ingredients as $ingredient) {
            $this->assertDatabaseMissing('user_shopping_lists', ['ingredient_id' => $ingredient->id, 'bar_memebership_id' => 1]);
        }
    }
}
