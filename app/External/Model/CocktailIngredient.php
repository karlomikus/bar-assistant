<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Illuminate\Support\Str;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\External\SupportsSchema4;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\AmountValueObject;
use Kami\Cocktail\Models\CocktailIngredient as CocktailIngredientModel;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

readonly class CocktailIngredient implements SupportsDataPack, SupportsSchema4
{
    /**
     * @param array<CocktailIngredientSubstitute> $substitutes
     */
    private function __construct(
        public Ingredient $ingredient,
        public AmountValueObject $amount,
        public bool $optional = false,
        public bool $isSpecified = false,
        public ?string $note = null,
        public array $substitutes = [],
        public int $sort = 0,
    ) {
    }

    public static function fromModel(CocktailIngredientModel $model, ?Units $toUnits = null): self
    {
        $substitutes = $model->substitutes->map(fn (CocktailIngredientSubstituteModel $substitute) => CocktailIngredientSubstitute::fromModel($substitute, $toUnits))->toArray();

        $amount = $model->getAmount();
        if ($toUnits && !$model->getAmount()->units->isDash()) {
            $amount = $amount->convertTo(new UnitValueObject($toUnits->value));
        }

        return new self(
            Ingredient::fromModelBasic($model->ingredient),
            $amount,
            (bool) $model->optional,
            (bool) $model->is_specified,
            $model->note,
            $substitutes,
            $model->sort,
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        $substitutes = [];
        foreach ($sourceArray['substitutes'] ?? [] as $sourceSubstitute) {
            if (is_array($sourceSubstitute)) {
                $substitutes[] = CocktailIngredientSubstitute::fromDataPackArray($sourceSubstitute);
            } else {
                $substitutes[] = CocktailIngredientSubstitute::fromDataPackArray([
                    'name' => (string) $sourceSubstitute,
                ]);
            }
        }

        return new self(
            Ingredient::fromDataPackArray([
                '_id' => Str::slug($sourceArray['name']),
                'name' => $sourceArray['name'],
            ]),
            new AmountValueObject(
                $sourceArray['amount'] ?? 0.0,
                new UnitValueObject($sourceArray['units']),
                $sourceArray['amount_max'] ?? null,
            ),
            $sourceArray['optional'] ?? false,
            $sourceArray['is_specified'] ?? false,
            $sourceArray['note'] ?? null,
            $substitutes,
            $sourceArray['sort'] ?? 0,
        );
    }

    public function toDataPackArray(): array
    {
        return [
            '_id' => $this->ingredient->id,
            'name' => $this->ingredient->name,
            'sort' => $this->sort,
            'amount' => $this->amount->amountMin,
            'units' => $this->amount->units->value,
            'optional' => $this->optional,
            'is_specified' => $this->isSpecified,
            'amount_max' => $this->amount->amountMax,
            'note' => $this->note,
            'substitutes' => array_map(fn ($model) => $model->toDataPackArray(), $this->substitutes),
        ];
    }

    public static function fromSchema4Array(array $sourceArray): self
    {
        $substitutes = [];
        foreach ($sourceArray['substitutes'] ?? [] as $sourceSubstitute) {
            $substitutes[] = CocktailIngredientSubstitute::fromSchema4Array($sourceSubstitute);
        }

        return new self(
            Ingredient::fromSchema4Array([
                '_id' => $sourceArray['_id'] ?? Str::slug((string) ($sourceArray['name'] ?? '')),
                'name' => $sourceArray['name'] ?? '',
            ]),
            new AmountValueObject(
                $sourceArray['amount'] ?? 0.0,
                new UnitValueObject($sourceArray['units'] ?? null),
                $sourceArray['amount_max'] ?? null,
            ),
            $sourceArray['optional'] ?? false,
            $sourceArray['is_specified'] ?? false,
            $sourceArray['note'] ?? null,
            $substitutes,
            $sourceArray['sort'] ?? 0,
        );
    }

    public function toSchema4Array(): array
    {
        return [
            'name' => $this->ingredient->name,
            'amount' => $this->amount->amountMin,
            'units' => $this->amount->units->value,
            'optional' => $this->optional,
            'amount_max' => $this->amount->amountMax,
            'note' => $this->note,
            'substitutes' => array_map(fn ($model) => $model->toSchema4Array(), $this->substitutes),
        ];
    }
}
