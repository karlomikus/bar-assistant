<?php

declare(strict_types=1);

namespace Kami\Cocktail\ETL;

use JsonSerializable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

class CocktailIngredientSubstitute implements JsonSerializable
{
    public function __construct(
        public readonly Ingredient $ingredient,
        public readonly ?float $amount = null,
        public readonly ?string $units = null,
        public readonly ?float $amountMax = null,
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
