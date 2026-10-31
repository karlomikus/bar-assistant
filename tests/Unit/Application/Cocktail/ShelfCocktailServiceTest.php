<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cocktail;

use PHPUnit\Framework\TestCase;
use BarAssistant\Application\Cocktail\ShelfCocktailService;
use Tests\Infrastructure\InMemoryComplexIngredientResolver;
use BarAssistant\Application\Cocktail\DTO\FindShelfCocktails;
use Tests\Infrastructure\InMemoryShelfCocktailQueryRepository;

final class ShelfCocktailServiceTest extends TestCase
{
    private InMemoryShelfCocktailQueryRepository $shelfCocktailQueryRepository;
    private InMemoryComplexIngredientResolver $complexIngredientResolver;
    private ShelfCocktailService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shelfCocktailQueryRepository = new InMemoryShelfCocktailQueryRepository();
        $this->complexIngredientResolver = new InMemoryComplexIngredientResolver();
        $this->service = new ShelfCocktailService(
            $this->shelfCocktailQueryRepository,
            $this->complexIngredientResolver,
        );
    }

    public function test_returns_no_cocktails_for_empty_ingredient_list(): void
    {
        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [],
        ));

        $this->assertSame([], $result);
    }

    public function test_finds_cocktails_fully_covered_by_shelf(): void
    {
        $this->shelfCocktailQueryRepository->addCocktail(100, 1, [10, 20]);
        $this->shelfCocktailQueryRepository->addCocktail(101, 1, [10, 30]);
        $this->shelfCocktailQueryRepository->addCocktail(102, 1, [40]);

        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [10, 20, 30],
        ));

        $this->assertSame([100, 101], $result);
    }

    public function test_does_not_return_cocktails_from_other_bars(): void
    {
        $this->shelfCocktailQueryRepository->addCocktail(100, 1, [10]);
        $this->shelfCocktailQueryRepository->addCocktail(200, 2, [10]);

        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [10],
        ));

        $this->assertSame([100], $result);
    }

    public function test_merges_makeable_complex_ingredients_into_shelf(): void
    {
        // Complex ingredient 30 is composed from parts 31 and 32
        $this->complexIngredientResolver->addComplexIngredient(30, [31, 32]);
        $this->shelfCocktailQueryRepository->addCocktail(100, 1, [10, 30]);

        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [10, 31, 32],
        ));

        $this->assertSame([100], $result);
    }

    public function test_skips_complex_ingredient_resolution_when_disabled(): void
    {
        $this->complexIngredientResolver->addComplexIngredient(30, [31, 32]);
        $this->shelfCocktailQueryRepository->addCocktail(100, 1, [10, 30]);

        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [10, 31, 32],
            matchComplexIngredients: false,
        ));

        $this->assertSame([], $result);
    }

    public function test_does_not_duplicate_ingredients_already_on_shelf(): void
    {
        // Main ingredient 10 is also directly on the shelf
        $this->complexIngredientResolver->addComplexIngredient(10, [11, 12]);
        $this->shelfCocktailQueryRepository->addCocktail(100, 1, [10]);

        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [10, 11, 12],
        ));

        $this->assertSame([100], $result);
    }

    public function test_deduplicates_input_ingredient_ids(): void
    {
        $this->shelfCocktailQueryRepository->addCocktail(100, 1, [10, 20]);

        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [10, 10, 20, 20],
        ));

        $this->assertSame([100], $result);
    }

    public function test_applies_limit(): void
    {
        $this->shelfCocktailQueryRepository->addCocktail(100, 1, [10]);
        $this->shelfCocktailQueryRepository->addCocktail(101, 1, [10]);
        $this->shelfCocktailQueryRepository->addCocktail(102, 1, [10]);

        $result = $this->service->findShelfCocktails(new FindShelfCocktails(
            barId: 1,
            ingredientIds: [10],
            limit: 2,
        ));

        $this->assertCount(2, $result);
    }
}
