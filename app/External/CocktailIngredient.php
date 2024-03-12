<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

use JsonSerializable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\CocktailIngredient as CocktailIngredientModel;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

readonly class CocktailIngredient implements JsonSerializable
{
    /**
     * @param array<CocktailIngredientSubstitute> $substitutes
     */
    private function __construct(
        public Ingredient $ingredient,
        public float $amount,
        public string $units,
        public bool $optional = false,
        public ?float $amountMax = null,
        public ?string $note = null,
        public array $substitutes = [],
        public int $sort = 0,
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
            $model->sort,
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        $substitutes = [];
        foreach ($sourceArray['substitutes'] ?? [] as $sourceSubstitute) {
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
                'category' => $sourceArray['category'] ?? null,
            ]),
            $sourceArray['amount'] ?? 0.0,
            $sourceArray['units'],
            $sourceArray['optional'] ?? false,
            $sourceArray['amount_max'] ?? null,
            $sourceArray['note'] ?? null,
            $substitutes,
            $sourceArray['sort'],
        );
    }

    public function toArray(): array
    {
        return [
            ...$this->ingredient->toArray(),
            'amount' => $this->amount,
            'units' => $this->units,
            'optional' => $this->optional,
            'amount_max' => $this->amountMax,
            'note' => $this->note,
            'substitutes' => array_map(fn ($model) => $model->toArray(), $this->substitutes),
            'sort' => $this->sort,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
