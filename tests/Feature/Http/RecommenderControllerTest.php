<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecommenderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommends_cocktails_based_on_favorites(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Create a favorite cocktail with tags
        $tag = Tag::factory()->recycle($membership->bar)->create(['name' => 'Tropical']);
        $favoriteCocktail = Cocktail::factory()
            ->recycle($membership->bar, $membership->user)
            ->create();
        $favoriteCocktail->tags()->attach($tag);

        // Mark as favorite
        CocktailFavorite::factory()->for($favoriteCocktail)->for($membership)->create();

        // Create a potential recommendation with the same tag
        $recommendedCocktail = Cocktail::factory()
            ->recycle($membership->bar, $membership->user)
            ->create(['name' => 'Recommended Cocktail']);
        $recommendedCocktail->tags()->attach($tag);

        // Create an unrelated cocktail
        Cocktail::factory()
            ->recycle($membership->bar, $membership->user)
            ->create(['name' => 'Unrelated Cocktail']);

        $response = $this->getJson('/api/recommender/cocktails', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertOk();
        $this->assertSame('Recommended Cocktail', $response->json('data.0.name'));
    }

    public function test_returns_empty_collection_when_no_favorites(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Create cocktails but don't mark any as favorite
        Cocktail::factory()
            ->recycle($membership->bar, $membership->user)
            ->count(5)
            ->create();

        $response = $this->getJson('/api/recommender/cocktails', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_excludes_already_favorited_cocktails_from_recommendations(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $tag = Tag::factory()->recycle($membership->bar)->create(['name' => 'Refreshing']);

        // Create and favorite two cocktails
        $favorite1 = Cocktail::factory()->recycle($membership->bar, $membership->user)->create();
        $favorite1->tags()->attach($tag);
        CocktailFavorite::factory()->for($favorite1)->for($membership)->create();

        $favorite2 = Cocktail::factory()->recycle($membership->bar, $membership->user)->create();
        $favorite2->tags()->attach($tag);
        CocktailFavorite::factory()->for($favorite2)->for($membership)->create();

        // Create a potential recommendation
        $recommendation = Cocktail::factory()->recycle($membership->bar, $membership->user)->create(['name' => 'New Recommendation']);
        $recommendation->tags()->attach($tag);

        $response = $this->getJson('/api/recommender/cocktails', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertOk();
        $cocktailIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertNotContains($favorite1->id, $cocktailIds);
        $this->assertNotContains($favorite2->id, $cocktailIds);
    }

    public function test_considers_bar_shelf_ingredients_in_recommendations(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        // Create ingredients
        $ingredient1 = Ingredient::factory()->recycle($membership->bar)->create(['name' => 'Rum']);
        $ingredient2 = Ingredient::factory()->recycle($membership->bar)->create(['name' => 'Lime']);

        // Add ingredients to bar shelf
        BarIngredient::factory()->for($membership->bar)->for($ingredient1)->create();
        BarIngredient::factory()->for($membership->bar)->for($ingredient2)->create();

        // Create favorite cocktail with ingredient1
        $favoriteCocktail = Cocktail::factory()->recycle($membership->bar, $membership->user)->create();
        CocktailIngredient::factory()->for($favoriteCocktail)->for($ingredient1)->create();
        CocktailFavorite::factory()->for($favoriteCocktail)->for($membership)->create();

        // Create recommendation cocktail with both shelf ingredients
        $recommendedCocktail = Cocktail::factory()->recycle($membership->bar, $membership->user)->create(['name' => 'Shelf Cocktail']);
        CocktailIngredient::factory()->for($recommendedCocktail)->for($ingredient1)->create();
        CocktailIngredient::factory()->for($recommendedCocktail)->for($ingredient2)->create();

        $response = $this->getJson('/api/recommender/cocktails', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_returns_404_when_user_not_member_of_bar(): void
    {
        $membership = $this->setupBarMembership();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        // Create favorite cocktail for the membership user
        $favoriteCocktail = Cocktail::factory()->recycle($membership->bar, $membership->user)->create();
        CocktailFavorite::factory()->for($favoriteCocktail)->for($membership)->create();

        $response = $this->getJson('/api/recommender/cocktails', ['Bar-Assistant-Bar-Id' => $membership->bar_id]);

        $response->assertForbidden();
    }
}
