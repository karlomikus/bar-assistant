<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Recommendation;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Recommendation\WeightedTag;
use BarAssistant\Domain\Recommendation\CocktailWithDetails;
use BarAssistant\Domain\Recommendation\RecommendationScoringService;

final class RecommendationScoringServiceTest extends TestCase
{
    private RecommendationScoringService $scoringService;

    protected function setUp(): void
    {
        $this->scoringService = new RecommendationScoringService();
    }

    public function test_score_returns_empty_array_when_no_cocktails(): void
    {
        $results = $this->scoringService->score(
            favoriteTags: [],
            negativeTags: [],
            favoriteIngredients: [],
            barShelfIngredientIds: [],
            cocktails: [],
        );

        $this->assertEmpty($results);
    }

    public function test_score_calculates_tag_match_score(): void
    {
        $favoriteTags = [
            new WeightedTag(tagName: 1, weight: 0.8),
            new WeightedTag(tagName: 2, weight: 1.6),
        ];

        $cocktails = [
            new CocktailWithDetails(
                cocktailId: new CocktailId(1),
                tags: [1, 2],
                ingredientIds: [],
                createdAt: null,
            ),
        ];

        $results = $this->scoringService->score(
            favoriteTags: $favoriteTags,
            negativeTags: [],
            favoriteIngredients: [],
            barShelfIngredientIds: [],
            cocktails: $cocktails,
        );

        $this->assertCount(1, $results);
        $this->assertEqualsWithDelta(2.4, $results[0]->score, 0.01);
        $this->assertEquals([1, 2], $results[0]->matchedTagIds);
    }

    public function test_score_applies_negative_tag_penalty(): void
    {
        $favoriteTags = [new WeightedTag(tagName: 1, weight: 1.0)];
        $negativeTags = [new WeightedTag(tagName: 2, weight: 2.0)];

        $cocktails = [
            new CocktailWithDetails(
                cocktailId: new CocktailId(1),
                tags: [1, 2],
                ingredientIds: [],
                createdAt: null,
            ),
        ];

        $results = $this->scoringService->score(
            favoriteTags: $favoriteTags,
            negativeTags: $negativeTags,
            favoriteIngredients: [],
            barShelfIngredientIds: [],
            cocktails: $cocktails,
        );

        $this->assertEqualsWithDelta(0.0, $results[0]->score, 0.01);
    }

    public function test_score_applies_shelf_ingredient_bonus(): void
    {
        $cocktails = [
            new CocktailWithDetails(
                cocktailId: new CocktailId(1),
                tags: [],
                ingredientIds: [new IngredientId(1), new IngredientId(2)],
                createdAt: null,
            ),
        ];

        $barShelfIngredients = [new IngredientId(1), new IngredientId(3)];

        $results = $this->scoringService->score(
            favoriteTags: [],
            negativeTags: [],
            favoriteIngredients: [],
            barShelfIngredientIds: $barShelfIngredients,
            cocktails: $cocktails,
        );

        $this->assertEqualsWithDelta(1.2, $results[0]->score, 0.01);
        $this->assertEqualsWithDelta(0.5, $results[0]->shelfCompleteness, 0.01);
    }

    public function test_score_applies_shelf_completeness_bonus(): void
    {
        $cocktails = [
            new CocktailWithDetails(
                cocktailId: new CocktailId(1),
                tags: [],
                ingredientIds: [
                    new IngredientId(1),
                    new IngredientId(2),
                    new IngredientId(3),
                    new IngredientId(4),
                ],
                createdAt: null,
            ),
        ];

        $barShelfIngredients = [new IngredientId(1), new IngredientId(2)];

        $results = $this->scoringService->score(
            favoriteTags: [],
            negativeTags: [],
            favoriteIngredients: [],
            barShelfIngredientIds: $barShelfIngredients,
            cocktails: $cocktails,
        );

        $this->assertEqualsWithDelta(1.9, $results[0]->score, 0.01);
        $this->assertEqualsWithDelta(0.5, $results[0]->shelfCompleteness, 0.01);
    }

    public function test_score_sorts_by_score_descending(): void
    {
        $cocktails = [
            new CocktailWithDetails(
                cocktailId: new CocktailId(1),
                tags: [],
                ingredientIds: [],
                createdAt: null,
            ),
            new CocktailWithDetails(
                cocktailId: new CocktailId(2),
                tags: [1],
                ingredientIds: [],
                createdAt: null,
            ),
            new CocktailWithDetails(
                cocktailId: new CocktailId(3),
                tags: [1, 2],
                ingredientIds: [],
                createdAt: null,
            ),
        ];

        $favoriteTags = [
            new WeightedTag(tagName: 1, weight: 1.0),
            new WeightedTag(tagName: 2, weight: 1.0),
        ];

        $results = $this->scoringService->score(
            favoriteTags: $favoriteTags,
            negativeTags: [],
            favoriteIngredients: [],
            barShelfIngredientIds: [],
            cocktails: $cocktails,
        );

        $this->assertEquals(new CocktailId(3), $results[0]->cocktailId);
        $this->assertEquals(new CocktailId(2), $results[1]->cocktailId);
        $this->assertEquals(new CocktailId(1), $results[2]->cocktailId);
    }

    public function test_score_applies_recency_boost(): void
    {
        $recentCocktail = new CocktailWithDetails(
            cocktailId: new CocktailId(1),
            tags: [],
            ingredientIds: [],
            createdAt: new DateTimeImmutable(),
        );

        $oldCocktail = new CocktailWithDetails(
            cocktailId: new CocktailId(2),
            tags: [],
            ingredientIds: [],
            createdAt: new DateTimeImmutable('-1 year'),
        );

        $results = $this->scoringService->score(
            favoriteTags: [],
            negativeTags: [],
            favoriteIngredients: [],
            barShelfIngredientIds: [],
            cocktails: [$recentCocktail, $oldCocktail],
        );

        $this->assertEquals(new CocktailId(1), $results[0]->cocktailId);
        $this->assertEquals(new CocktailId(2), $results[1]->cocktailId);
    }

    public function test_score_limits_results(): void
    {
        $cocktails = [];
        for ($i = 1; $i <= 20; $i++) {
            $cocktails[] = new CocktailWithDetails(
                cocktailId: new CocktailId($i),
                tags: [$i],
                ingredientIds: [],
                createdAt: null,
            );
        }

        $favoriteTags = [new WeightedTag(tagName: 1, weight: 1.0)];

        $results = $this->scoringService->score(
            favoriteTags: $favoriteTags,
            negativeTags: [],
            favoriteIngredients: [],
            barShelfIngredientIds: [],
            cocktails: $cocktails,
        );

        $this->assertCount(20, $results);
    }
}
