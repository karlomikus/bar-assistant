<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShoppingListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_ingredients_on_shopping_list_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        UserShoppingList::factory()->count(5)->create();
        UserShoppingList::factory()
            ->recycle($membership, $membership->bar, $membership->user)
            ->count(5)
            ->create();

        $response = $this->getJson('/api/users/'. $membership->user_id .'/shopping-list', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

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
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);
        $ingredients = Ingredient::factory()->recycle($membership->bar)->count(3)->create();

        $response = $this->postJson('/api/users/'. $membership->user_id .'/shopping-list/batch-store', [
            'ingredients' => $ingredients->map(fn ($ing) => ['id' => $ing->id])->toArray()
        ], ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertNoContent();
    }

    public function test_add_multiple_ingredients_to_shopping_list_from_another_bar_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $unOwnedIngredients = Ingredient::factory()->count(3)->create();

        $response = $this->postJson('/api/users/'. $membership->user_id .'/shopping-list/batch-store', [
            'ingredients' => $unOwnedIngredients->map(fn ($ing) => ['id' => $ing->id])->toArray()
        ], ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertNoContent();
        $this->assertDatabaseCount('user_shopping_lists', 0);
    }

    public function test_delete_multiple_ingredients_from_shopping_list_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredients = UserShoppingList::factory()
            ->recycle($membership, $membership->bar, $membership->user)
            ->count(5)
            ->create();

        $this->assertDatabaseCount('user_shopping_lists', 5);

        $response = $this->postJson('/api/users/'. $membership->user_id .'/shopping-list/batch-delete', [
            'ingredients' => $ingredients->pluck('ingredient')->map(fn ($ing) => ['id' => $ing->id])->toArray()
        ], ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertNoContent();

        $this->assertDatabaseCount('user_shopping_lists', 0);
    }
}
