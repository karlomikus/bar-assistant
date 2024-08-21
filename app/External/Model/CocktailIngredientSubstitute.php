<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Illuminate\Support\Str;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\External\SupportsDraft2;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

readonly class CocktailIngredientSubstitute implements SupportsDataPack, SupportsDraft2
{
    private function __construct(
        public IngredientBasic $ingredient,
        public ?float $amount = null,
        public ?string $units = null,
        public ?float $amountMax = null,
    ) {
    }

    public static function fromModel(CocktailIngredientSubstituteModel $model): self
    {
        return new self(
            IngredientBasic::fromModel($model->ingredient),
            $model->amount,
            $model->units,
            $model->amount_max,
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        return new self(
            IngredientBasic::fromDataPackArray([
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

    public function toDataPackArray(): array
    {
        return [
            ...$this->ingredient->toDataPackArray(),
            'amount' => $this->amount,
            'units' => $this->units,
            'amount_max' => $this->amountMax,
        ];
    }

    public static function fromDraft2Array(array $sourceArray): self
    {
        return new self(
            IngredientBasic::fromDraft2Array([
                '_id' => $sourceArray['_id'],
                'name' => $sourceArray['name'] ?? '',
            ]),
            $sourceArray['amount'] ?? null,
            $sourceArray['units'] ?? null,
            $sourceArray['amount_max'] ?? null,
        );
    }

    public function toDraft2Array(): array
    {
        return [
            '_id' => $this->ingredient->id,
            'amount' => $this->amount,
            'units' => $this->units,
            'amount_max' => $this->amountMax,
        ];
    }
}
