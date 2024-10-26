<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\UserIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShelfControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_ingredients_on_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        UserIngredient::factory()
            ->recycle($membership, $membership->bar, $membership->user)
            ->count(5)
            ->create();

        $response = $this->getJson('/api/users/'. $membership->user_id .'/ingredients', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 5)
                ->etc()
        );
    }

    public function test_add_multiple_ingredients_to_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $newIngredients = Ingredient::factory()
            ->recycle($membership->bar)
            ->count(5)
            ->create();

        $response = $this->postJson('/api/users/'. $membership->user_id .'/ingredients/batch-store', [
            'ingredients' => $newIngredients->pluck('id')->toArray()
        ], ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertNoContent();
        $this->assertDatabaseHas('user_ingredients', ['ingredient_id' => $newIngredients->pluck('id')->toArray(), 'bar_membership_id' => $membership->id]);
    }

    public function test_add_multiple_ingredients_from_another_bar_to_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        Ingredient::factory()
            ->recycle($membership->bar)
            ->count(5)
            ->create();

        $unOwnedIngredients = Ingredient::factory()
            ->count(5)
            ->create();

        $response = $this->postJson('/api/users/'. $membership->user_id .'/ingredients/batch-store', [
            'ingredients' => $unOwnedIngredients->pluck('id')->toArray()
        ], ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('user_ingredients', ['bar_membership_id' => $membership->id]);
    }

    public function test_delete_multiple_ingredients_from_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredients = UserIngredient::factory()
            ->recycle($membership, $membership->bar, $membership->user)
            ->count(5)
            ->create();

        $this->assertDatabaseCount('user_ingredients', 5);
        $response = $this->postJson('/api/users/'. $membership->user_id .'/ingredients/batch-delete', [
            'ingredients' => $ingredients->splice(0, 2)->pluck('id')->toArray()
        ], ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertNoContent();
        $this->assertDatabaseCount('user_ingredients', 3);
    }

    public function test_delete_multiple_ingredients_from_another_bar_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ownedIngredients = UserIngredient::factory()
            ->recycle($membership, $membership->bar, $membership->user)
            ->count(5)
            ->create();

        $unOwnedIngredients = UserIngredient::factory()
            ->count(5)
            ->create();

        $response = $this->postJson('/api/users/'. $membership->user_id .'/ingredients/batch-delete', [
            'ingredients' => $unOwnedIngredients->splice(0, 2)->pluck('id')->toArray()
        ], ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertNoContent();
        $this->assertDatabaseHas('user_ingredients', ['ingredient_id' => $ownedIngredients->pluck('id')->toArray(), 'bar_membership_id' => $membership->id]);
    }

    public function test_list_ingredients_on_bar_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        BarIngredient::factory()
            ->recycle($membership->bar, $membership->user)
            ->count(5)
            ->create();

        $response = $this->getJson('/api/bars/'. $membership->bar_id .'/ingredients');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 5)
                ->etc()
        );
    }

    public function test_add_multiple_ingredients_to_bar_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $newIngredients = Ingredient::factory()
            ->recycle($membership->bar)
            ->count(5)
            ->create();

        $response = $this->postJson('/api/bars/'. $membership->bar_id .'/ingredients/batch-store', [
            'ingredients' => $newIngredients->pluck('id')->toArray()
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('bar_ingredients', ['ingredient_id' => $newIngredients->pluck('id')->toArray(), 'bar_id' => $membership->bar_id]);
    }

    public function test_delete_multiple_ingredients_from_bar_shelf_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredients = BarIngredient::factory()
            ->recycle($membership->bar, $membership->user)
            ->count(5)
            ->create();

        $this->assertDatabaseCount('bar_ingredients', 5);
        $response = $this->postJson('/api/bars/'. $membership->user_id .'/ingredients/batch-delete', [
            'ingredients' => $ingredients->splice(0, 2)->pluck('id')->toArray()
        ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('bar_ingredients', 3);
    }
}
