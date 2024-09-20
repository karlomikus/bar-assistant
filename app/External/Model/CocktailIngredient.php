<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Illuminate\Support\Str;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\External\SupportsDraft2;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\CocktailIngredientFormatter;
use Kami\Cocktail\Models\CocktailIngredient as CocktailIngredientModel;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

readonly class CocktailIngredient implements SupportsDataPack, SupportsDraft2
{
    /**
     * @param array<CocktailIngredientSubstitute> $substitutes
     */
    private function __construct(
        public IngredientBasic $ingredient,
        public float $amount,
        public ?string $units,
        public bool $optional = false,
        public ?float $amountMax = null,
        public ?string $note = null,
        public array $substitutes = [],
        public int $sort = 0,
        public ?CocktailIngredientFormatter $formatter = null,
    ) {
    }

    public static function fromModel(CocktailIngredientModel $model, ?Units $toUnits = null): self
    {
        $substitutes = $model->substitutes->map(function (CocktailIngredientSubstituteModel $substitute) {
            return CocktailIngredientSubstitute::fromModel($substitute);
        })->toArray();

        return new self(
            IngredientBasic::fromModel($model->ingredient),
            $model->amount,
            $model->units,
            (bool) $model->optional,
            $model->amount_max,
            $model->note,
            $substitutes,
            $model->sort,
            $model->getConvertedTo($toUnits),
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
            IngredientBasic::fromDataPackArray([
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
            $sourceArray['sort'] ?? 0,
        );
    }

    public function toDataPackArray(): array
    {
        return [
            ...$this->ingredient->toDataPackArray(),
            'amount' => $this->formatter?->getAmount() ?? $this->amount,
            'units' => $this->formatter?->getUnits() ?? $this->units,
            'optional' => $this->optional,
            'amount_max' => $this->formatter?->getMaxAmount() ?? $this->amountMax,
            'note' => $this->note,
            'substitutes' => array_map(fn ($model) => $model->toDataPackArray(), $this->substitutes),
            'sort' => $this->sort,
        ];
    }

    public static function fromDraft2Array(array $sourceArray): self
    {
        $substitutes = [];
        foreach ($sourceArray['substitutes'] ?? [] as $sourceSubstitute) {
            if (is_array($sourceSubstitute)) {
                $substitutes[] = CocktailIngredientSubstitute::fromDraft2Array($sourceSubstitute);
            } else {
                $substitutes[] = CocktailIngredientSubstitute::fromDraft2Array([
                    'name' => (string) $sourceSubstitute,
                ]);
            }
        }

        return new self(
            IngredientBasic::fromDraft2Array([
                '_id' => $sourceArray['_id'],
                'name' => $sourceArray['name'] ?? '',
            ]),
            $sourceArray['amount'] ?? 0.0,
            $sourceArray['units'],
            $sourceArray['optional'] ?? false,
            $sourceArray['amount_max'] ?? null,
            $sourceArray['note'] ?? null,
            $substitutes,
            $sourceArray['sort'] ?? 0,
        );
    }

    public function toDraft2Array(): array
    {
        return [
            '_id' => $this->ingredient->id,
            'amount' => $this->formatter?->getAmount() ?? $this->amount,
            'units' => $this->formatter?->getUnits() ?? $this->units,
            'optional' => $this->optional,
            'amount_max' => $this->formatter?->getMaxAmount() ?? $this->amountMax,
            'note' => $this->note,
            'substitutes' => array_map(fn ($model) => $model->toDraft2Array(), $this->substitutes),
            'sort' => $this->sort,
        ];
    }
}
