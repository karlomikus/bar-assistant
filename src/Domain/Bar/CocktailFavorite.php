<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use DateTimeImmutable;
use BarAssistant\Domain\Cocktail\CocktailId;

final readonly class CocktailFavorite
{
    private function __construct(
        public CocktailId $cocktailId,
        public DateTimeImmutable $favoritedAt,
    ) {
    }

    public static function create(CocktailId $cocktailId): self
    {
        return new self($cocktailId, new DateTimeImmutable());
    }

    public static function createWithTimestamp(CocktailId $cocktailId, DateTimeImmutable $favoritedAt): self
    {
        return new self($cocktailId, $favoritedAt);
    }
}
