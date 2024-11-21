<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Stringable;
use JsonSerializable;
use Kami\RecipeUtils\UnitConverter\Units;

final readonly class UnitValueObject implements Stringable, JsonSerializable
{
    public string $value;

    public function __construct(
        string $value,
    ) {
        $this->value = trim(mb_strtolower($value));
    }

    public function getAsEnum(): ?Units
    {
        if ($this->isDash()) {
            return Units::Dash;
        }

        if ($this->isBarspoon()) {
            return Units::Barspoon;
        }

        return Units::tryFrom($this->value);
    }

    public function isDash(): bool
    {
        return str_contains($this->value, 'dash');
    }

    public function isBarspoon(): bool
    {
        return str_contains($this->value, 'spoon') || $this->value === 'tsp';
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
