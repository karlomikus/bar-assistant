<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Collection;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_response(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        Ingredient::factory()->for($membership->bar)->count(5)->create();
        Cocktail::factory()->for($membership->bar)->count(5)->create();
        $userIngredients = UserIngredient::factory()
            ->recycle($membership, $membership->bar, $membership->user)
            ->count(5)
            ->create();

        $ingredient = $userIngredients->first()->ingredient;
        Collection::factory()->recycle($membership->bar)->for($membership)->count(10)->create();

        $cocktail1 = Cocktail::factory()
            ->for($membership->bar)
            ->hasTags(['name' => 'Tag 1'])
            ->has(
                CocktailIngredient::factory()
                    ->state(['optional' => false])
                    ->for($ingredient)
                    ->recycle($membership->bar),
                'ingredients'
            )
            ->create();

        CocktailFavorite::factory()
            ->for($cocktail1)
            ->for($membership)
            ->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->getJson('/api/bars/' . $membership->bar_id . '/stats');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.total_cocktails', 6)
                ->where('data.total_ingredients', 10)
                ->where('data.total_shelf_ingredients', 5)
                ->where('data.total_shelf_cocktails', 1)
                ->where('data.total_favorited_cocktails', 1)
                ->where('data.total_collections', 10)
                ->where('data.total_bar_members', 1)
                ->has('data.favorite_tags', 1)
                ->has('data.your_top_ingredients', 1)
                ->has('data.most_popular_ingredients', 1)
                ->where('data.top_rated_cocktails', [])
                ->etc()
        );
    }
}
