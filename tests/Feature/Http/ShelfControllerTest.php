<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserRoleEnum;
use Kami\Cocktail\Models\UserIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShelfControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
        $this->setupBar();
    }

    public function test_list_ingredients_on_shelf_response(): void
    {
        $ingredients = Ingredient::factory()->count(5)->create(['bar_id' => 1]);

        foreach ($ingredients as $ingredient) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $ingredient->id;
            $userIngredient->bar_membership_id = 1;
            $userIngredient->save();
        }

        $response = $this->getJson('/api/shelf/ingredients?bar_id=1');

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
        $newIngredients = Ingredient::factory()->count(2)->create(['bar_id' => 1]);

        $response = $this->postJson('/api/shelf/ingredients/batch-store?bar_id=1', [
            'ingredient_ids' => $newIngredients->pluck('id')->toArray()
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 2)
                ->etc()
        );
    }

    public function test_add_multiple_ingredients_from_another_bar_to_shelf_response(): void
    {
        Bar::factory()->create(['id' => 2]);
        DB::table('bar_memberships')->insert(['id' => 2, 'bar_id' => 2, 'user_id' => 1, 'user_role_id' => UserRoleEnum::Admin->value]);
        $ing1 = Ingredient::factory()->create(['bar_id' => 1]);
        $ing2 = Ingredient::factory()->create(['bar_id' => 2]);

        $response = $this->postJson('/api/shelf/ingredients/batch-store?bar_id=1', [
            'ingredient_ids' => [$ing1->id, $ing2->id]
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 1)
                ->where('data.0.ingredient_id', $ing1->id)
                ->etc()
        );
    }

    public function test_delete_multiple_ingredients_from_shelf_response(): void
    {
        $ing1 = Ingredient::factory()->create(['bar_id' => 1]);
        $ing2 = Ingredient::factory()->create(['bar_id' => 1]);

        $userIngredient = new UserIngredient();
        $userIngredient->ingredient_id = $ing1->id;
        $userIngredient->bar_membership_id = 1;
        $userIngredient->save();

        $userIngredient = new UserIngredient();
        $userIngredient->ingredient_id = $ing2->id;
        $userIngredient->bar_membership_id = 1;
        $userIngredient->save();

        $response = $this->postJson('/api/shelf/ingredients/batch-delete?bar_id=1', [
            'ingredient_ids' => [$ing1->id, $ing2->id]
        ]);

        $response->assertNoContent();

        $this->assertDatabaseMissing('user_ingredients', ['ingredient_id' => $ing1->id]);
        $this->assertDatabaseMissing('user_ingredients', ['ingredient_id' => $ing2->id]);
    }

    public function test_delete_multiple_ingredients_from_another_bar_response(): void
    {
        Bar::factory()->create(['id' => 2]);
        DB::table('bar_memberships')->insert(['id' => 2, 'bar_id' => 2, 'user_id' => 1, 'user_role_id' => UserRoleEnum::Admin->value]);
        $ing1 = Ingredient::factory()->create(['bar_id' => 1]);
        $ing2 = Ingredient::factory()->create(['bar_id' => 2]);

        $userIngredient = new UserIngredient();
        $userIngredient->ingredient_id = $ing1->id;
        $userIngredient->bar_membership_id = 1;
        $userIngredient->save();

        $userIngredient = new UserIngredient();
        $userIngredient->ingredient_id = $ing2->id;
        $userIngredient->bar_membership_id = 2;
        $userIngredient->save();

        $response = $this->postJson('/api/shelf/ingredients/batch-delete?bar_id=1', [
            'ingredient_ids' => [$ing1->id, $ing2->id]
        ]);

        $response->assertNoContent();

        $this->assertDatabaseMissing('user_ingredients', ['ingredient_id' => $ing1->id]);
        $this->assertDatabaseHas('user_ingredients', ['ingredient_id' => $ing2->id]);
    }

    public function test_show_cocktail_ids_on_shelf_response(): void
    {
        $response = $this->getJson('/api/shelf/cocktails?bar_id=1');

        $response->assertStatus(200);
    }
}
