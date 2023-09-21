<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->setupBar();
    }

    public function test_stats_response(): void
    {
        $ingredients = Ingredient::factory()->count(5)->create(['bar_id' => 1]);
        $cocktails = Cocktail::factory()->count(6)->create(['bar_id' => 1]);

        $userIngredient = new UserIngredient();
        $userIngredient->ingredient_id = $ingredients->first()->id;
        $userIngredient->bar_membership_id = 1;
        $userIngredient->save();

        $favorite = new CocktailFavorite();
        $favorite->cocktail_id = $cocktails->first()->id;
        $favorite->bar_membership_id = 1;
        $favorite->save();

        $response = $this->getJson('/api/stats?bar_id=1');

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('data.total_cocktails', 6)
                ->where('data.total_ingredients', 5)
                ->where('data.total_shelf_cocktails', 0)
                ->where('data.total_shelf_ingredients', 1)
                ->where('data.total_favorited_cocktails', 1)
                ->where('data.most_popular_ingredients', [])
                ->where('data.top_rated_cocktails', [])
                ->where('data.total_collections', 0)
                ->where('data.your_top_ingredients', [])
                ->etc()
        );
    }
}
