<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Collection;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_totals_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredients = Ingredient::factory()->for($membership->bar)->count(5)->create();
        Cocktail::factory()->for($membership->bar)->count(5)->create();

        $ingredient = $ingredients->first();
        BarIngredient::factory()->create([
            'ingredient_id' => $ingredient->id,
            'bar_id' => $membership->bar_id,
        ]);

        Collection::factory()->recycle($membership->bar)->for($membership)->count(10)->create();

        $cocktail = $this->createFavoritedCocktailWithIngredient(
            membership: $membership,
            ingredient: $ingredient,
            tagName: 'Tag 1',
        );

        CocktailFavorite::factory()
            ->for($cocktail)
            ->for($membership)
            ->create();

        $response = $this->getJson('/api/bars/' . $membership->bar_id . '/stats/totals');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.total_cocktails', 6)
                ->where('data.total_ingredients', 5)
                ->where('data.total_bar_shelf_ingredients', 1)
                ->where('data.total_bar_shelf_cocktails', 1)
                ->where('data.total_favorited_cocktails', 1)
                ->where('data.total_collections', 10)
                ->where('data.total_bar_members', 1)
                ->etc()
        );
    }

    public function test_top_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create(['name' => 'Lime Juice']);
        $cocktail = $this->createFavoritedCocktailWithIngredient(
            membership: $membership,
            ingredient: $ingredient,
            tagName: 'Citrus',
        );

        CocktailFavorite::factory()
            ->for($cocktail)
            ->for($membership)
            ->create();

        $response = $this->getJson('/api/bars/' . $membership->bar_id . '/stats/top');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.top_bar_cocktails', [])
                ->has('data.top_member_ingredients', 1)
                ->where('data.top_member_ingredients.0.name', 'Lime Juice')
                ->where('data.top_member_ingredients.0.cocktails_count', 1)
                ->etc()
        );
    }

    public function test_taste_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $ingredient = Ingredient::factory()->for($membership->bar)->create();
        $cocktail = $this->createFavoritedCocktailWithIngredient(
            membership: $membership,
            ingredient: $ingredient,
            tagName: 'Tag 1',
        );

        CocktailFavorite::factory()
            ->for($cocktail)
            ->for($membership)
            ->create();

        $response = $this->getJson('/api/bars/' . $membership->bar_id . '/stats/taste');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.favorite_tags', 1)
                ->where('data.favorite_tags.0.name', 'Tag 1')
                ->where('data.favorite_tags.0.weight', 1)
                ->has('data.disliked_tags')
                ->has('data.abv_distribution')
                ->etc()
        );
    }

    public function test_ingredient_distribution_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $rootIngredient = Ingredient::factory()->for($membership->bar)->create([
            'name' => 'Spirits',
            'parent_ingredient_id' => null,
        ]);
        $childIngredient = Ingredient::factory()->for($membership->bar)->create([
            'name' => 'Gin',
            'parent_ingredient_id' => $rootIngredient->id,
        ]);

        BarIngredient::factory()->create([
            'ingredient_id' => $childIngredient->id,
            'bar_id' => $membership->bar_id,
        ]);

        $response = $this->getJson('/api/bars/' . $membership->bar_id . '/stats/ingredient-distribution');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.main_category_ingredient_distribution', 1)
                ->where('data.main_category_ingredient_distribution.0.name', 'Spirits')
                ->where('data.main_category_ingredient_distribution.0.ingredients_count', 1)
                ->etc()
        );
    }

    private function createFavoritedCocktailWithIngredient(BarMembership $membership, Ingredient $ingredient, string $tagName): Cocktail
    {
        return Cocktail::factory()
            ->for($membership->bar)
            ->hasTags(['name' => $tagName])
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();
    }
}
