<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Brick\Money\Currency;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use Kami\Cocktail\Models\PriceCategory as Model;
use BarAssistant\Domain\Ingredient\PriceCategory;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\PriceCategoryRepository;

final class EloquentPriceCategoryRepository implements PriceCategoryRepository
{
    public function findById(PriceCategoryId $id): ?PriceCategory
    {
        $model = Model::find($id->value);

        if ($model === null) {
            return null;
        }

        return $this->map($model);
    }

    public function save(PriceCategory $priceCategory): PriceCategory
    {
        $model = Model::findOrNew($priceCategory->getId()?->value);

        $model->bar_id = $priceCategory->getBarId()->value;
        $model->name = (string) $priceCategory->getName();
        $model->currency = $priceCategory->getCurrency()->getCurrencyCode();
        $model->description = $priceCategory->getDescription();
        $model->save();

        return $this->map($model);
    }

    public function findMany(BarId $barId, array $ids): array
    {
        $models = Model::whereIn('id', array_map(fn (PriceCategoryId $id) => $id->value, $ids))->where('bar_id', $barId->value)->get();

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
            name: Name::fromString($model->name),
            currency: Currency::of($model->currency),
            description: $model->description,
        )->setId(new PriceCategoryId($model->id));

        return $priceCategory;
    }
}
