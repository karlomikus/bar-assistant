<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientMatch;
use BarAssistant\Domain\Ingredient\IngredientMatchRepository;

/**
 * In-memory implementation of IngredientMatchRepository for testing purposes
 */
final class InMemoryIngredientMatchRepository implements IngredientMatchRepository
{
    /**
     * @param array<int, IngredientMatch> $matches
     */
    public function __construct(private array $matches = [])
    {
    }

    public function add(IngredientMatch $match): void
    {
        $this->matches[] = $match;
    }

    public function findManyByBarId(BarId $barId): array
    {
        return $this->matches;
    }
}
