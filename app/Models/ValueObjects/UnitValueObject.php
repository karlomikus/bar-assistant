<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Stringable;
use JsonSerializable;
use Kami\RecipeUtils\Parser\Parser;
use Kami\RecipeUtils\UnitConverter\Units;

final readonly class UnitValueObject implements Stringable, JsonSerializable
{
    public string $value;

    /** @var array<string, array<string>> */
    public array $units;

    public function __construct(
        ?string $value,
    ) {
        $this->units = (new Parser())->getUnits();
        $this->value = trim(mb_strtolower($value ?? ''));
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

    public function isTopup(): bool
    {
        return str_contains($this->value, 'topup') || str_contains($this->value, 'to top');
    }

    public function isDash(): bool
    {
        $matches = $this->units['dash'] ?? [];

        return in_array($this->value, $matches, true);
    }

    public function isBarspoon(): bool
    {
        $matches = $this->units['barspoon'] ?? [];

        return str_contains($this->value, 'spoon') || in_array($this->value, $matches, true);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
