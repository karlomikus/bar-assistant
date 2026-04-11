<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Bar\MemberId;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentRecommendationRepository;

class EloquentRecommendationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_gets_applicable_cocktails(): void
    {
        $barMembership = $this->setupBarMembership();
        $cocktails = Cocktail::factory()->for($barMembership->bar)->count(10)->create();
        CocktailFavorite::factory()->for($cocktails->first())->for($barMembership)->create();
        $cocktails[1]->rate(3, $barMembership->user_id);

        $repository = new EloquentRecommendationRepository();
        $results = $repository->getApplicableCocktails(new MemberId($barMembership->id));

        $this->assertCount(8, $results);
    }

    public function test_it_gets_negative_tags(): void
    {
        $barMembership = $this->setupBarMembership();
        $tag1 = Tag::factory()->for($barMembership->bar)->create();
        $tag2 = Tag::factory()->for($barMembership->bar)->create();
        $tag3 = Tag::factory()->for($barMembership->bar)->create();
        $cocktail1 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail1->tags()->saveMany([$tag1, $tag2, $tag3]);
        $cocktail2 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail2->tags()->saveMany([$tag1]);
        $cocktail3 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail3->tags()->saveMany([$tag1]);

        $cocktail1->rate(1, $barMembership->user_id);
        $cocktail2->rate(1, $barMembership->user_id);
        $cocktail3->rate(1, $barMembership->user_id);

        $repository = new EloquentRecommendationRepository();
        $results = $repository->getNegativeTags(new MemberId($barMembership->id));

        $this->assertCount(1, $results);
        $this->assertSame($tag1->name, $results[0]->tagName);
    }

    public function test_it_gets_favorite_tags(): void
    {
        $barMembership = $this->setupBarMembership();
        $tag1 = Tag::factory()->for($barMembership->bar)->create();
        $tag2 = Tag::factory()->for($barMembership->bar)->create();
        $tag3 = Tag::factory()->for($barMembership->bar)->create();
        $cocktail1 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail1->tags()->saveMany([$tag1, $tag2, $tag3]);
        $cocktail2 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail2->tags()->saveMany([$tag1]);
        $cocktail3 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail3->tags()->saveMany([$tag1]);

        CocktailFavorite::factory()->for($cocktail1)->for($barMembership)->create();
        CocktailFavorite::factory()->for($cocktail2)->for($barMembership)->create();
        CocktailFavorite::factory()->for($cocktail3)->for($barMembership)->create();

        $repository = new EloquentRecommendationRepository();
        $results = $repository->getFavoriteTags(new MemberId($barMembership->id));

        $this->assertCount(3, $results);
    }

    public function test_it_gets_favorite_ingredients(): void
    {
        $barMembership = $this->setupBarMembership();
        $ingredient1 = Ingredient::factory()->for($barMembership->bar)->create();
        $ingredient2 = Ingredient::factory()->for($barMembership->bar)->create();

        $cocktail1 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail2 = Cocktail::factory()->for($barMembership->bar)->create();
        $cocktail3 = Cocktail::factory()->for($barMembership->bar)->create();

        CocktailIngredient::factory()->for($cocktail1)->for($ingredient1)->create();
        CocktailIngredient::factory()->for($cocktail1)->for($ingredient2)->create();
        CocktailIngredient::factory()->for($cocktail2)->for($ingredient1)->create();
        CocktailIngredient::factory()->for($cocktail3)->for($ingredient1)->create();

        CocktailFavorite::factory()->for($cocktail1)->for($barMembership)->create();
        CocktailFavorite::factory()->for($cocktail2)->for($barMembership)->create();
        CocktailFavorite::factory()->for($cocktail3)->for($barMembership)->create();

        $repository = new EloquentRecommendationRepository();
        $results = $repository->getFavoriteIngredients(new MemberId($barMembership->id));

        $this->assertCount(2, $results);
    }
}
