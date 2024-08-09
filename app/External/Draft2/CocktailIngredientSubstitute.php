<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Draft2;

use JsonSerializable;
use Kami\Cocktail\Models\CocktailIngredientSubstitute as CocktailIngredientSubstituteModel;

readonly class CocktailIngredientSubstitute implements JsonSerializable
{
    private function __construct(
        public string $id,
        public ?float $amount = null,
        public ?string $units = null,
        public ?float $amountMax = null,
    ) {
    }

    public static function fromModel(CocktailIngredientSubstituteModel $model): self
    {
        return new self(
            $model->ingredient->getExternalId(),
            $model->amount,
            $model->units,
            $model->amount_max,
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        return new self(
            $sourceArray['_id'],
            $sourceArray['amount'] ?? null,
            $sourceArray['units'] ?? null,
            $sourceArray['amount_max'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            '_id' => $this->id,
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
