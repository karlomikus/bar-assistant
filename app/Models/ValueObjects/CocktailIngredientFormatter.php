<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

final readonly class CocktailIngredientFormatter
{
    public function __construct(
        private AmountValueObject $amount,
        private string $name,
        private bool $optional = false,
    ) {
    }

    public function format(): string
    {
        $name = $this->name;
        $optional = $this->optional === true ? ' (optional)' : '';

        return trim(sprintf('%s %s%s', (string) $this->amount, $name, $optional));
    }
}
