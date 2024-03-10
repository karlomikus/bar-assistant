<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use JsonSerializable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

readonly class CocktailIngredientSubstitute implements JsonSerializable
{
    private function __construct(
        public Ingredient $ingredient,
        public ?float $amount = null,
        public ?string $units = null,
        public ?float $amountMax = null,
    ) {
    }

    public static function fromModel(CocktailIngredientSubstituteModel $model): self
    {
        return new self(
            Ingredient::fromModel($model->ingredient),
            $model->amount,
            $model->units,
            $model->amount_max,
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        return new self(
            Ingredient::fromArray([
                '_id' => Str::slug($sourceArray['name']),
                'name' => $sourceArray['name'],
                'strength' => $sourceArray['strength'] ?? 0.0,
                'description' => $sourceArray['description'] ?? null,
                'origin' => $sourceArray['origin'] ?? null,
                'category' => $sourceArray['category'] ?? null,
            ]),
            $sourceArray['amount'] ?? null,
            $sourceArray['units'] ?? null,
            $sourceArray['amount_max'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            ...$this->ingredient->toArray(),
            'amount' => $this->amount,
            'units' => $this->units,
            'amount_max' => $this->amountMax,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
