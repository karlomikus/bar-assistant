<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Stringable;
use JsonSerializable;
use Kami\RecipeUtils\UnitConverter\Units;

final readonly class UnitValueObject implements Stringable, JsonSerializable
{
    public string $value;

    /** @var array<string, array<string>> */
    public array $units;

    public function __construct(
        ?string $value,
    ) {
        // TODO: Move to package
        $this->units = [
            'oz' => ['oz.', 'fl-oz', 'oz', 'ounce', 'ounces'],
            'ml' => ['ml', 'ml.', 'milliliter', 'milliliters'],
            'cl' => ['cl', 'cl.', 'centiliter', 'centiliters'],
            'dash' => ['dashes', 'dash', 'ds'],
            'sprigs' => ['sprig', 'sprigs'],
            'leaves' => ['leaves', 'leaf'],
            'whole' => ['whole'],
            'drops' => ['drop', 'drops'],
            'barspoon' => ['barspoon', 'teaspoon', 'bsp', 'tsp', 'tsp.', 'tspn', 't', 't.', 'teaspoon', 'teaspoons', 'tablespoons', 'tablespoon'],
            'slice' => ['slice', 'sliced', 'slices'],
            'cup' => ['c', 'c.', 'cup', 'cups'],
            'pint' => ['pt', 'pts', 'pt.', 'pint', 'pints'],
            'splash' => ['a splash', 'splash', 'splashes'],
            'pinch' => ['pinches', 'pinch'],
            'topup' => ['topup'],
            'part' => ['part', 'parts'],
            'wedge' => ['wedge', 'wedges'],
            'cube' => ['cubes', 'cube'],
            'bottle' => ['bottles', 'bottle'],
            'can' => ['cans', 'can'],
            'bag' => ['bags', 'bag'],
            'shot' => ['shots', 'shot'],
        ];
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
        $matches = $this->units['dash'];

        return in_array($this->value, $matches, true);
    }

    public function isBarspoon(): bool
    {
        $matches = $this->units['barspoon'];

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
