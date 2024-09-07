<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\IngredientPrice as IngredientPriceModel;

readonly class IngredientPrice implements SupportsDataPack
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

    public static function fromDataPackArray(array $sourceArray): self
    {
        return new self(
            $sourceArray['price_category_name'],
            $sourceArray['price'],
            $sourceArray['currency'],
            $sourceArray['amount'],
            $sourceArray['units'],
        );
    }

    public function toDataPackArray(): array
    {
        return [
            'price_category_name' => $this->priceCategoryName,
            'price' => $this->price,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'units' => $this->units,
        ];
    }
}
