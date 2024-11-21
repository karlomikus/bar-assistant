<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Illuminate\Support\Str;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\External\SupportsDraft2;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\AmountValueObject;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

readonly class CocktailIngredientSubstitute implements SupportsDataPack, SupportsDraft2
{
    private function __construct(
        public IngredientBasic $ingredient,
        public ?AmountValueObject $amount = null,
    ) {
    }

    public static function fromModel(CocktailIngredientSubstituteModel $model, ?Units $toUnits = null): self
    {
        $amount = $model->getAmount();

        if ($toUnits && !$amount->units->isDash()) {
            $amount = $amount->convertTo(new UnitValueObject($toUnits->value));
        }

        return new self(
            IngredientBasic::fromModel($model->ingredient),
            $amount,
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
            new AmountValueObject(
                $sourceArray['amount'] ?? 0.0,
                $sourceArray['units'] ?? '',
                $sourceArray['amount_max'] ?? null,
            ),
        );
    }

    public function toDataPackArray(): array
    {
        return [
            ...$this->ingredient->toDataPackArray(),
            'amount' => $this->amount->amountMin <= 0.0 ? null : $this->amount->amountMin,
            'units' => $this->amount->units->value === '' ? null : $this->amount->units->value,
            'amount_max' => $this->amount->amountMax,
        ];
    }

    public static function fromDraft2Array(array $sourceArray): self
    {
        return new self(
            IngredientBasic::fromDraft2Array([
                '_id' => $sourceArray['_id'],
                'name' => $sourceArray['name'] ?? '',
            ]),
            new AmountValueObject(
                $sourceArray['amount'] ?? 0.0,
                $sourceArray['units'] ?? '',
                $sourceArray['amount_max'] ?? null,
            ),
        );
    }

    public function toDraft2Array(): array
    {
        return [
            '_id' => $this->ingredient->id,
            'amount' => $this->amount->amountMin <= 0.0 ? null : $this->amount->amountMin,
            'units' => $this->amount->units->value === '' ? null : $this->amount->units->value,
            'amount_max' => $this->amount->amountMax,
        ];
    }
}
