<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\DataPack;

use JsonSerializable;
use Kami\Cocktail\Models\IngredientPrice as IngredientPriceModel;

readonly class IngredientPrice implements JsonSerializable
{
    private function __construct(
        public string $priceCategoryName,
        public int $price,
        public string $currency,
        public float $amount,
        public string $units,
    ) {
    }

    public static function fromModel(IngredientPriceModel $model): self
    {
        return new self(
            $model->priceCategory->name,
            $model->price,
            $model->priceCategory->currency,
            $model->amount,
            $model->units,
        );
    }

    public function toArray(): array
    {
        return [
            'price_category_name' => $this->priceCategoryName,
            'price' => $this->price,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'units' => $this->units,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
