<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Recommendation;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Recommendation\WeightedTag;
use BarAssistant\Domain\Recommendation\WeightedIngredient;
use BarAssistant\Domain\Recommendation\CocktailWithDetails;
use BarAssistant\Domain\Recommendation\RecommendationScoringService;

final class RecommendationScoringServiceTest extends TestCase
{
    public function test_score_ranks_cocktail_with_matching_preferences_higher(): void
    {
        $service = new RecommendationScoringService();

        $gin = new IngredientId(1);
        $lemon = new IngredientId(2);
        $tequila = new IngredientId(3);

        // Cocktail A: matches favorite tag and favorite ingredients, fully on shelf
        $cocktailA = new CocktailWithDetails(
            cocktailId: new CocktailId(1),
            tags: ['Sour', 'Citrus'],
            ingredientIds: [$gin, $lemon],
            createdAt: null,
        );

        // Cocktail B: no preference matches, nothing on shelf
        $cocktailB = new CocktailWithDetails(
            cocktailId: new CocktailId(2),
            tags: ['Tropical'],
            ingredientIds: [$tequila],
            createdAt: null,
        );

        $results = $service->score(
            favoriteTags: [new WeightedTag('Sour', 1.0), new WeightedTag('Citrus', 0.5)],
            negativeTags: [],
            favoriteIngredients: [
                new WeightedIngredient($gin, Name::fromString('Gin'), 1.0),
                new WeightedIngredient($lemon, Name::fromString('Lemon'), 0.8),
            ],
            barShelfIngredientIds: [$gin, $lemon],
            cocktails: [$cocktailA, $cocktailB],
        );

        $this->assertCount(2, $results);
        $this->assertSame(1, $results[0]->cocktailId->value);
        $this->assertGreaterThan($results[1]->score, $results[0]->score);
        $this->assertEqualsWithDelta(1.0, $results[0]->score, 0.2);
    }
}
