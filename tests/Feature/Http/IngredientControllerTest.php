<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Carbon\Carbon;
use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\UserShoppingList;
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

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_paginate_ingredients_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        Ingredient::factory()->for($membership->bar)->count(55)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->getJson('/api/ingredients');

        $response->assertOk();
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

    public function test_list_ingredients_unknown_bar_response(): void
    {
        Bar::factory()->create(['id' => 2]);
        Ingredient::factory()->count(1)->create();

        $response = $this->getJson('/api/ingredients?bar_id=2');

        $response->assertForbidden();
    }

    public function test_list_ingredients_response_filters(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $user = User::factory()->create();
        $ingredientCategory = IngredientCategory::factory()->for($membership->bar)->create();
        $ingredients = Ingredient::factory()->for($membership->bar)->createMany([
            ['name' => 'Whiskey', 'origin' => 'fix-string', 'strength' => 35.5, 'created_at' => Carbon::now()->addDays(1)],
            ['name' => 'XXXX', 'strength' => 0, 'created_at' => Carbon::now()->addDays(2)],
            ['name' => 'Test', 'created_user_id' => $user->id, 'strength' => 40, 'created_at' => Carbon::now()->addDays(3)],
            ['name' => 'Test 2', 'ingredient_category_id' => $ingredientCategory->id, 'strength' => 0, 'created_at' => Carbon::now()->addDays(4)],
        ]);

        Cocktail::factory()
            ->for($membership->bar)
            ->has(CocktailIngredient::factory()->state([
                'ingredient_id' => $ingredients->first()->id,
                'sort' => 1,
            ]), 'ingredients')
            ->create([
                'name' => 'A cocktail name',
            ]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->getJson('/api/ingredients?filter[name]=whi');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[name]=whi,xx');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/ingredients?filter[created_user_id]=' . $user->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[category_id]=' . $ingredientCategory->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[origin]=fix-string');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[strength_min]=30');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/ingredients?filter[strength_max]=39');
        $response->assertJsonCount(3, 'data');
        $response = $this->getJson('/api/ingredients?filter[on_shelf]=true');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/ingredients?filter[on_shopping_list]=true');
        $response->assertJsonCount(0, 'data');
        $response = $this->getJson('/api/ingredients?filter[main_ingredients]=true');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?sort=-total_cocktails');
        $response->assertJsonPath('data.0.name', 'Whiskey');
        $response = $this->getJson('/api/ingredients?sort=-strength');
        $response->assertJsonPath('data.0.name', 'Test');
        $response = $this->getJson('/api/ingredients?sort=created_at');
        $response->assertJsonPath('data.0.name', 'Whiskey');
    }

    public function test_list_ingredients_response_filter_by_shopping_list(): void
    {
        $bar = $this->setupBar();
        $ingredients = Ingredient::factory()->count(5)->create(['bar_id' => $bar->id]);
        foreach ($ingredients as $ing) {
            $rel = new UserShoppingList();
            $rel->ingredient_id = $ing->id;
            $rel->bar_membership_id = 1;
            $rel->save();
        }
        Ingredient::factory()->count(5)->create(['bar_id' => $bar->id]);

        $response = $this->getJson('/api/ingredients?bar_id=1&filter[on_shopping_list]=true');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_list_ingredients_response_filter_by_shelf(): void
    {
        $bar = $this->setupBar();
        $ingredients = Ingredient::factory()->count(5)->create(['bar_id' => $bar->id]);
        foreach ($ingredients as $ing) {
            $rel = new UserIngredient();
            $rel->ingredient_id = $ing->id;
            $rel->bar_membership_id = 1;
            $rel->save();
        }
        Ingredient::factory()->count(5)->create(['bar_id' => $bar->id]);

        $response = $this->getJson('/api/ingredients?bar_id=1&filter[on_shelf]=true');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    }

    public function test_ingredient_show_response(): void
    {
        $bar = $this->setupBar();
        $ingredient = Ingredient::factory()
            ->create([
                'slug' => 'test-ingredient-1',
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test',
                'origin' => 'Croatia',
                'color' => '#fff',
                'bar_id' => $bar->id,
            ]);

        Ingredient::factory()
            ->state([
                'name' => 'Child ingredient',
                'strength' => 45.5,
                'parent_ingredient_id' => $ingredient->id,
                'bar_id' => $bar->id,
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
        $response->assertJsonPath('data.slug', 'test-ingredient-1');
        $response->assertJsonPath('data.name', 'Test ingredient');
        $response->assertJsonPath('data.strength', 45.5);
        $response->assertJsonPath('data.description', 'Test');
        $response->assertJsonPath('data.origin', 'Croatia');
        $response->assertJsonPath('data.main_image_id', null);
        $response->assertJsonPath('data.images', []);
        $response->assertJsonPath('data.category.id', 1);
        $response->assertJsonPath('data.parent_ingredient.id', null);
        $response->assertJsonPath('data.color', '#fff');
        $response->assertJsonPath('data.cocktails_count', 1);
        $response->assertJsonCount(1, 'data.varieties');
    }

    public function test_ingredient_show_not_found_response(): void
    {
        $response = $this->getJson('/api/ingredients/404');

        $response->assertStatus(404);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('message')
                ->etc()
        );
    }

    public function test_ingredient_store_response(): void
    {
        $this->setupBar();
        $ingCat = IngredientCategory::factory()->create(['bar_id' => 1]);

        $response = $this->postJson('/api/ingredients?bar_id=1', [
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
                ->etc()
        );
    }

    public function test_ingredient_store_fails_validation_response(): void
    {
        $this->setupBar();

        $response = $this->postJson('/api/ingredients?bar_id=1', [
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
    }

    public function test_ingredient_update_response(): void
    {
        $this->setupBar();
        $ing = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test',
                'bar_id' => 1,
                'created_user_id' => auth()->user()->id,
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
    }

    public function test_ingredient_update_fails_validation_response(): void
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
    }

    public function test_ingredient_delete_response(): void
    {
        $this->setupBar();
        $ing = Ingredient::factory()
            ->state([
                'name' => 'Test ingredient',
                'strength' => 45.5,
                'description' => 'Test',
                'bar_id' => 1,
                'created_user_id' => auth()->user()->id,
            ])
            ->create();

        $response = $this->deleteJson('/api/ingredients/' . $ing->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('ingredients', ['id' => $ing->id]);
    }

    public function test_ingredients_extra_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient1 = Ingredient::factory()->for($membership->bar)->create();
        $ingredient2 = Ingredient::factory()->for($membership->bar)->create();
        UserIngredient::factory()->for($membership)->for($ingredient1)->create();

        $cocktail = Cocktail::factory()
            ->has(CocktailIngredient::factory()->for(
                $ingredient1
            ), 'ingredients')
            ->has(CocktailIngredient::factory()->for(
                $ingredient2
            ), 'ingredients')
            ->create(['bar_id' => 1]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->getJson('/api/ingredients/' . $ingredient1->id . '/extra');
        $response->assertJsonCount(0, 'data');

        $response = $this->getJson('/api/ingredients/' . $ingredient2->id . '/extra');

        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 1, function (AssertableJson $jsonIng) use ($cocktail) {
                    $jsonIng
                        ->where('id', $cocktail->id)
                        ->where('slug', $cocktail->slug)
                        ->where('name', $cocktail->name);
                })
        );
    }

    public function test_token_read_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs(
            $user,
            abilities: ['unknown.read']
        );
        $this->setupBar();

        $response = $this->getJson('/api/ingredients?bar_id=1');
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['ingredients.read']
        );

        $response = $this->getJson('/api/ingredients?bar_id=1');
        $response->assertOk();
    }

    public function test_token_write_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs(
            $user,
            abilities: ['ingredients.read']
        );
        $this->setupBar();

        $response = $this->postJson('/api/ingredients?bar_id=1', []);
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['ingredients.write']
        );

        $response = $this->postJson('/api/ingredients?bar_id=1', ['name' => 'Test']);
        $response->assertCreated();
    }
}
