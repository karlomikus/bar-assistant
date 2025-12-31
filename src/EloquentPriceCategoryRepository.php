<?php

declare(strict_types=1);

namespace BarAssistant;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use Kami\Cocktail\Models\PriceCategory as Model;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;
use Brick\Money\Currency;

final class EloquentPriceCategoryRepository implements PriceCategoryRepository
{
    public function findMany(BarId $barId, array $ids): array
    {
        $models = Model::whereIn('id', array_map(fn(PriceCategoryId $id) => $id->id, $ids))->where('bar_id', $barId->id)->get();

        $ingredients = [];
        /** @var Model $model */
        foreach ($models as $model) {
            $ingredients[] = $this->map($model);
        }

        return $ingredients;
    }

    private function map(Model $model): PriceCategory
    {
        $priceCategory = new PriceCategory(
            barId: new BarId($model->bar_id),
            name: $model->name,
            currency: Currency::of($model->currency),
            description: $model->description,
        )->setId(new PriceCategoryId($model->id));

        return $priceCategory;
    }
}
