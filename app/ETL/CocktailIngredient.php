<?php

declare(strict_types=1);

namespace Kami\Cocktail\ETL;

use Illuminate\Support\Str;
use Kami\Cocktail\Models\CocktailIngredient as CocktailIngredientModel;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

class CocktailIngredient
{
    /**
     * @param array<CocktailIngredientSubstitute> $substitutes
     */
    public function __construct(
        public readonly Ingredient $ingredient,
        public readonly float $amount,
        public readonly string $units,
        public readonly bool $optional = false,
        public readonly ?float $amountMax = null,
        public readonly ?string $note = null,
        public readonly array $substitutes = [],
    ) {
    }

    public static function fromModel(CocktailIngredientModel $model): self
    {
        $substitutes = $model->substitutes->map(function (CocktailIngredientSubstituteModel $substitute) {
            return CocktailIngredientSubstitute::fromModel($substitute);
        })->toArray();

        return new self(
            Ingredient::fromModel($model->ingredient),
            $model->amount,
            $model->units,
            (bool) $model->optional,
            $model->amount_max,
            $model->note,
            $substitutes,
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        $substitutes = [];
        foreach ($sourceArray['substitutes'] as $sourceSubstitute) {
            if (is_array($sourceSubstitute)) {
                $substitutes[] = CocktailIngredientSubstitute::fromArray($sourceSubstitute);
            } else {
                $substitutes[] = CocktailIngredientSubstitute::fromArray([
                    'name' => (string) $sourceSubstitute,
                ]);
            }
        }

        return new self(
            Ingredient::fromArray([
                '_id' => Str::slug($sourceArray['name']),
                'name' => $sourceArray['name'],
                'strength' => $sourceArray['strength'] ?? 0.0,
                'description' => $sourceArray['description'] ?? null,
                'origin' => $sourceArray['origin'] ?? null,
            ]),
            $sourceArray['amount'] ?? 0.0,
            $sourceArray['units'],
            $sourceArray['optional'] ?? false,
            $sourceArray['amount_max'] ?? null,
            $sourceArray['note'] ?? null,
            $substitutes
        );
    }
}
