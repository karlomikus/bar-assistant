<?php

declare(strict_types=1);

namespace BarAssistant;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientPrice;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\Support\AmountWithUnits;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Domain\Support\Price;
use BarAssistant\Domain\Support\Unit;
use BarAssistant\Domain\User\UserId;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\Models\IngredientPrice as ModelIngredientPrice;

final class EloquentIngredientRepository implements IngredientRepository
{
    public function findById(IngredientId $id): ?Ingredient
    {
        $model = ModelIngredient::find($id->id);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    /** @param IngredientId[] $ids */
    public function findMany(BarId $barId, array $ids): array
    {
        $models = ModelIngredient::whereIn('id', array_map(fn(IngredientId $id) => $id->id, $ids))->where('bar_id', $barId->id)->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    public function save(Ingredient $ingredient): Ingredient
    {
        DB::beginTransaction();

        $ingredientModel = ModelIngredient::findOrNew($ingredient->getId()?->id);

        try {
            $ingredientModel->bar_id = $ingredient->getBarId()->id;
            $ingredientModel->name = $ingredient->getName();
            $ingredientModel->strength = $ingredient->getStrength();
            $ingredientModel->description = $ingredient->getDescription();
            $ingredientModel->origin = $ingredient->getOrigin();
            $ingredientModel->color = $ingredient->getColor()?->toHexString();
            $ingredientModel->created_user_id = $ingredient->getAuthors()->getCreatedBy()->id;
            $ingredientModel->created_at = $ingredient->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
            $ingredientModel->calculator_id = null;
            $ingredientModel->sugar_g_per_ml = null;
            $ingredientModel->acidity = null;
            $ingredientModel->distillery = null;
            $ingredientModel->units = null;
            $ingredientModel->materialized_path = $ingredient->getMaterializedPath()->toString();
            $ingredientModel->parent_ingredient_id = $ingredient->getParentIngredientId()?->id;
            $ingredientModel->save();

            foreach ($ingredient->getIngredientParts() as $ingredientPartId) {
                $partModel = new ComplexIngredient();
                $partModel->ingredient_id = $ingredientPartId->id;
                $partModel->main_ingredient_id = $ingredientModel->id;
                $partModel->save();
            }

            foreach ($ingredient->getPrices() as $price) {
                $ingredientPriceModel = new ModelIngredientPrice();
                $ingredientPriceModel->ingredient_id = $ingredientModel->id;
                $ingredientPriceModel->price_category_id = $price->getPriceCategoryId()->id;
                $ingredientPriceModel->price = $price->getPrice()->getPriceAsMinor();
                $ingredientPriceModel->amount = $price->getAmountWithUnits()->amountMin;
                $ingredientPriceModel->units = $price->getAmountWithUnits()->units;
                $ingredientPriceModel->description = $price->getDescription();
                $ingredientPriceModel->save();
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();

        if ($ingredient->isTransient()) {
            $ingredient = $ingredient->setId(new IngredientId($ingredientModel->id));
        }

        return $ingredient;
    }

    public function saveHierarchyChanges(Ingredient $ingredient, array $descendants): void
    {
        DB::transaction(function () use ($ingredient, $descendants): void {
            $this->save($ingredient);

            foreach ($descendants as $descendant) {
                $this->save($descendant);
            }
        });
    }

    public function findDescendants(Ingredient $ingredient): array
    {
        if ($ingredient->getMaterializedPath()->isRoot()) {
            return [];
        }

        $models = ModelIngredient::where('materialized_path', 'LIKE', $ingredient->getMaterializedPath()->toString() . '%')->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    private static function map(ModelIngredient $model): Ingredient
    {
        $ingredient = new Ingredient(
            barId: new BarId($model->bar_id),
            materializedPath: MaterializedPath::fromString($model->materialized_path),
            parentIngredientId: $model->parent_ingredient_id ? new IngredientId($model->parent_ingredient_id) : null,
        );

        $ingredient->setId(new IngredientId($model->id));

        $ingredient->updateDetails(
            name: $model->name,
            description: $model->description,
            strength: $model->strength,
            origin: $model->origin,
            color: $model->color ? Color::fromHexString($model->color) : null,
        );

        $ingredient->wasCreatedBy(new UserId($model->created_user_id), $model->created_at->toDateTimeImmutable());
        if ($model->updated_user_id !== null) {
            $ingredient->wasUpdatedBy(new UserId($model->updated_user_id), $model->updated_at?->toDateTimeImmutable());
        }

        /** @var ComplexIngredient $part */
        foreach ($model->ingredientParts as $part) {
            $ingredient->addIngredientPart(self::map($part->ingredient));
        }

        /** @var ModelIngredientPrice $price */
        foreach ($model->prices as $price) {
            $ingredient->addPrice(
                new IngredientPrice(
                    priceCategoryId: new \BarAssistant\Domain\Ingredient\PriceCategoryId($price->price_category_id),
                    price: Price::createFromMinor($price->price, $price->priceCategory->currency),
                    amountWithUnits: new AmountWithUnits($price->amount, new Unit($price->units)),
                    description: $price->description,
                )
            );
        }

        return $ingredient;
    }
}
