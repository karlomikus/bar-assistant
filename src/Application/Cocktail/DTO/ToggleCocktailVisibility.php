<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class ToggleCocktailVisibility
{
    public function __construct(
        public int $cocktailId,
        public ?ForceCocktailVisibility $forceVisibility = null,
    ) {
    }
}
