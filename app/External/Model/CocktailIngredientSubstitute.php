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
        public Ingredient $ingredient,
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
            Ingredient::fromModel($model->ingredient),
            $amount,
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        return new self(
            Ingredient::fromDataPackArray([
                '_id' => Str::slug($sourceArray['name']),
                'name' => $sourceArray['name'],
            ]),
            new AmountValueObject(
                $sourceArray['amount'] ?? 0.0,
                new UnitValueObject($sourceArray['units'] ?? ''),
                $sourceArray['amount_max'] ?? null,
            ),
        );
    }

    public function toDataPackArray(): array
    {
        return [
            '_id' => $this->ingredient->id,
            'name' => $this->ingredient->name,
            'amount' => $this->amount->amountMin <= 0.0 ? null : $this->amount->amountMin,
            'units' => $this->amount->units->value === '' ? null : $this->amount->units->value,
            'amount_max' => $this->amount->amountMax,
        ];
    }

    public static function fromDraft2Array(array $sourceArray): self
    {
        return new self(
            Ingredient::fromDraft2Array([
                '_id' => $sourceArray['_id'],
                'name' => $sourceArray['name'] ?? '',
            ]),
            new AmountValueObject(
                $sourceArray['amount'] ?? 0.0,
                new UnitValueObject($sourceArray['units'] ?? ''),
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
