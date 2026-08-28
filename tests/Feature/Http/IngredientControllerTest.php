<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Carbon\Carbon;
use Tests\TestCase;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\UserShoppingList;
use Kami\Cocktail\Models\CocktailIngredient;
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

        $this->withHeader('Bar-Assistant-Bar-Id', '2');
        $response = $this->getJson('/api/ingredients');

        $response->assertForbidden();
    }

    public function test_list_ingredients_response_filters(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $user = User::factory()->create();
        $ingredients = Ingredient::factory()->for($membership->bar)->createMany([
            ['name' => 'Whiskey', 'origin' => 'fix-string', 'strength' => 35.5, 'created_at' => Carbon::now()->addDays(1)],
            ['name' => 'XXXX', 'strength' => 0, 'created_at' => Carbon::now()->addDays(2)],
            ['name' => 'Test', 'created_user_id' => $user->id, 'strength' => 40, 'created_at' => Carbon::now()->addDays(3)],
            ['name' => 'Test 2', 'strength' => 0, 'created_at' => Carbon::now()->addDays(4)],
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

        foreach ($ingredients as $ingredient) {
            BarIngredient::factory()->for($membership->bar)->for($ingredient)->create();
        }

        foreach ($ingredients as $ingredient) {
            UserIngredient::factory()->for($membership)->for($ingredient)->create();
        }

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->getJson('/api/ingredients?filter[name]=whi');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[name]=whi,xx');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/ingredients?filter[created_user_id]=' . $user->id);
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[origin]=fix-string');
        $response->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/ingredients?filter[strength_min]=30');
        $response->assertJsonCount(2, 'data');
        $response = $this->getJson('/api/ingredients?filter[strength_max]=39');
        $response->assertJsonCount(3, 'data');
        $response = $this->getJson('/api/ingredients?filter[on_shelf]=true');
        $response->assertJsonCount(4, 'data');
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
        $response = $this->getJson('/api/ingredients?filter[bar_shelf]=true');
        $response->assertJsonCount(4, 'data');
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

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);
        $response = $this->getJson('/api/ingredients?filter[on_shopping_list]=true');

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

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);
        $response = $this->getJson('/api/ingredients?filter[on_shelf]=true');

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
        $response->assertJsonPath('data.parent_ingredient.id', null);
        $response->assertJsonPath('data.color', '#fff');
        $response->assertJsonPath('data.cocktails_count', 1);
        // $response->assertJsonCount(1, 'data.varieties');
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
        $bar = $this->setupBar();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);
        $response = $this->postJson('/api/ingredients', [
            'name' => "Ingredient name",
            'strength' => 12.2,
            'description' => "Description text",
            'origin' => "Worldwide",
            'color' => "#000000",
            'parent_ingredient_id' => null
        ]);

        $response->assertStatus(201);
    }

    public function test_ingredient_store_fails_validation_response(): void
    {
        $bar = $this->setupBar();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);
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
                'created_user_id' => auth('sanctum')->user()->id,
            ])
            ->create();

        $response = $this->putJson('/api/ingredients/' . $ing->id, [
            'name' => "Ingredient name",
            'strength' => 12.2,
            'description' => "Description text",
            'origin' => "Worldwide",
            'color' => "#000000",
            'parent_ingredient_id' => null
        ]);

        $response->assertNoContent();
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
                'created_user_id' => auth('sanctum')->user()->id,
            ])
            ->create();

        $response = $this->deleteJson('/api/ingredients/' . $ing->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('ingredients', ['id' => $ing->id]);
    }

    public function test_token_read_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs(
            $user,
            abilities: ['unknown.read']
        );
        $bar = $this->setupBar();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);
        $response = $this->getJson('/api/ingredients');
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['ingredients.read']
        );

        $response = $this->getJson('/api/ingredients');
        $response->assertOk();
    }

    public function test_token_write_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs(
            $user,
            abilities: ['ingredients.read']
        );
        $bar = $this->setupBar();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $bar->id);
        $response = $this->postJson('/api/ingredients', []);
        $response->assertForbidden();

        $this->actingAs(
            $user,
            abilities: ['ingredients.write']
        );

        $response = $this->postJson('/api/ingredients', ['name' => 'Test']);
        $response->assertCreated();
    }

    public function test_list_ingredients_filter_by_origin(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Ingredients in the bar
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Scotch 1', 'origin' => 'Scotland', 'distillery' => 'Buffalo Trace']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Scotch 2', 'origin' => 'Scotland', 'distillery' => 'Highland Park']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Sake', 'origin' => 'Japan', 'distillery' => 'Ozeki']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Null origin', 'origin' => null]);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Empty origin', 'origin' => '']);

        // Ingredient in another bar with same origin
        $otherBar = Bar::factory()->create();
        Ingredient::factory()->for($otherBar)->create(['name' => 'Other Scotch', 'origin' => 'Scotland']);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // Single origin match
        $response = $this->getJson('/api/ingredients?filter[origin]=Scotland');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        // Multiple origins (OR match)
        $response = $this->getJson('/api/ingredients?filter[origin]=Scotland,Japan');
        $response->assertOk();
        $response->assertJsonCount(3, 'data');

        // Exact match required (partial does not match)
        $response = $this->getJson('/api/ingredients?filter[origin]=Scot');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Nonexistent origin
        $response = $this->getJson('/api/ingredients?filter[origin]=Nonexistent');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Ingredients with null or empty origin are never returned when origin filter is active
        $response = $this->getJson('/api/ingredients?filter[origin]=Scotland');
        $response->assertJsonMissing(['name' => 'Null origin']);
        $response->assertJsonMissing(['name' => 'Empty origin']);

        // Omitted/empty value is a no-op (returns all 5 ingredients in this bar)
        $response = $this->getJson('/api/ingredients?filter[origin]=');
        $response->assertOk();
        $response->assertJsonCount(5, 'data');

        // Empty names in the comma-separated list are ignored
        $response = $this->getJson('/api/ingredients?filter[origin]=Scotland,,Japan');
        $response->assertOk();
        $response->assertJsonCount(3, 'data');

        // Origin filter combines with other filters using AND
        $response = $this->getJson('/api/ingredients?filter[origin]=Scotland&filter[distillery]=Highland%20Park');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Scotch 2');
    }

    public function test_list_ingredients_filter_by_distillery(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Ingredients in the bar
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Bourbon', 'distillery' => 'Buffalo Trace']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Scotch', 'distillery' => 'Highland Park']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Vodka', 'distillery' => 'Grey Goose']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Null distillery', 'distillery' => null]);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Empty distillery', 'distillery' => '']);

        // Ingredient in another bar with same distillery
        $otherBar = Bar::factory()->create();
        Ingredient::factory()->for($otherBar)->create(['name' => 'Other Bourbon', 'distillery' => 'Buffalo Trace']);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // Single distillery match
        $response = $this->getJson('/api/ingredients?filter[distillery]=Buffalo%20Trace');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        // Multiple distilleries (OR match)
        $response = $this->getJson('/api/ingredients?filter[distillery]=Buffalo%20Trace,Highland%20Park');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        // Exact match required (partial does not match)
        $response = $this->getJson('/api/ingredients?filter[distillery]=Buffalo');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Nonexistent distillery
        $response = $this->getJson('/api/ingredients?filter[distillery]=Nonexistent');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // Ingredients with null or empty distillery are never returned when distillery filter is active
        $response = $this->getJson('/api/ingredients?filter[distillery]=Buffalo%20Trace');
        $response->assertJsonMissing(['name' => 'Null distillery']);
        $response->assertJsonMissing(['name' => 'Empty distillery']);

        // Omitted/empty value is a no-op (returns all 5 ingredients in this bar)
        $response = $this->getJson('/api/ingredients?filter[distillery]=');
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    public function test_list_ingredients_meta_filters_origins_distilleries(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Ingredients in the bar (with duplicate origins, null and empty values)
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Drink 1', 'origin' => 'Scotland', 'distillery' => 'Buffalo Trace']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Drink 2', 'origin' => 'Japan', 'distillery' => 'Highland Park']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Drink 3', 'origin' => 'Scotland', 'distillery' => 'Buffalo Trace']);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Drink 4', 'origin' => null, 'distillery' => null]);
        Ingredient::factory()->for($membership->bar)->create(['name' => 'Drink 5', 'origin' => '', 'distillery' => '']);

        // Ingredient in another bar
        $otherBar = Bar::factory()->create();
        Ingredient::factory()->for($otherBar)->create(['name' => 'Other Drink', 'origin' => 'Croatia', 'distillery' => 'Dingle']);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // Unfiltered request lists distinct origins and distilleries for this bar only, sorted alphabetically
        $response = $this->getJson('/api/ingredients');
        $response->assertOk();
        $response->assertJsonPath('meta.filters.origins', [
            ['name' => 'Japan'],
            ['name' => 'Scotland'],
        ]);
        $response->assertJsonPath('meta.filters.distilleries', [
            ['name' => 'Buffalo Trace'],
            ['name' => 'Highland Park'],
        ]);

        // Filtered request still includes the bar's full distinct sets, not the filtered subset
        $response = $this->getJson('/api/ingredients?filter[origin]=Scotland');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.filters.origins', [
            ['name' => 'Japan'],
            ['name' => 'Scotland'],
        ]);
        $response->assertJsonPath('meta.filters.distilleries', [
            ['name' => 'Buffalo Trace'],
            ['name' => 'Highland Park'],
        ]);
    }

    public function test_parent_ingredient_descendants_filtering(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $spirits = Ingredient::factory()->for($membership->bar)->create([
            'name' => 'Spirits',
            'materialized_path' => null,
        ]);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $response = $this->postJson('/api/ingredients/', [
            'name' => "Genever",
            'parent_ingredient_id' => $spirits->id,
        ]);

        $geneverId = (int) basename($response->headers->get('Location'));
        $genever = Ingredient::find($geneverId);

        $response = $this->postJson('/api/ingredients/', [
            'name' => "Gin",
            'parent_ingredient_id' => $genever->id,
        ]);

        $ginId = (int) basename($response->headers->get('Location'));
        $gin = Ingredient::find($ginId);

        $response = $this->postJson('/api/ingredients/', [
            'name' => "Bombay Sapphire",
            'parent_ingredient_id' => $gin->id,
        ]);

        $response = $this->postJson('/api/ingredients/', [
            'name' => "Old tom gin",
            'parent_ingredient_id' => $genever->id,
        ]);

        $response = $this->postJson('/api/ingredients/', [
            'name' => "Grain based",
            'parent_ingredient_id' => $spirits->id,
        ]);

        $grainId = (int) basename($response->headers->get('Location'));
        $grain = Ingredient::find($grainId);

        $response = $this->getJson('/api/ingredients?filter[descendants_of]=' . $genever->id);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.name', 'Bombay Sapphire');
        $response->assertJsonPath('data.1.name', 'Gin');
        $response->assertJsonPath('data.2.name', 'Old tom gin');

        $response = $this->getJson('/api/ingredients?filter[descendants_of]=' . $grain->id);
        $response->assertJsonCount(0, 'data');

        $response = $this->getJson('/api/ingredients?filter[descendants_of]=' . $gin->id);
        $response->assertJsonCount(1, 'data');
    }
}
