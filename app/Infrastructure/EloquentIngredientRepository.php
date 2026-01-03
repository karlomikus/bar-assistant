<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Throwable;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Image\ImageId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Support\Unit;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Domain\Support\Price;
use Kami\Cocktail\Models\ComplexIngredient;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Support\AmountWithUnits;
use BarAssistant\Domain\Ingredient\IngredientPrice;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Kami\Cocktail\Models\Image as ModelImage;
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
            $ingredientModel->calculator_id = $ingredient->getCalculatorId()?->id;
            $ingredientModel->sugar_g_per_ml = $ingredient->getSugarContent();
            $ingredientModel->acidity = $ingredient->getAcidity();
            $ingredientModel->distillery = $ingredient->getDistillery();
            $ingredientModel->units = $ingredient->getUnits()?->value;
            $ingredientModel->materialized_path = $ingredient->getMaterializedPath()->toString();
            $ingredientModel->parent_ingredient_id = $ingredient->getParentIngredientId()?->id;
            if ($ingredient->getAuthors()->isUpdated()) {
                $ingredientModel->updated_user_id = $ingredient->getAuthors()->getUpdatedBy()?->id;
                $ingredientModel->updated_at = $ingredient->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
                $ingredientModel->save();
            }
            $ingredientModel->save();

            if ($ingredient->isTransient()) {
                $ingredient = $ingredient->setId(new IngredientId($ingredientModel->id));
            }

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
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();

        if (count($ingredient->getImages()) > 0) {
            $imageModels = ModelImage::findOrFail(array_map(fn (ImageId $img): int => $img->id, $ingredient->getImages()));
            $ingredientModel->attachImages($imageModels);
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

        $models = ModelIngredient::where('materialized_path', 'like', $ingredient->getMaterializedPath()->append($ingredient->getId())->toString() . '%')->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    public function delete(IngredientId $id): void
    {
        ModelIngredient::destroy($id->id);
    }

    public function findChildren(IngredientId $parentId): array
    {
        $models = ModelIngredient::where('parent_ingredient_id', $parentId->id)->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    public function findAncestors(IngredientId $descendantId): array
    {
        $model = ModelIngredient::find($descendantId->id);

        if ($model === null) {
            return [];
        }

        $materializedPath = MaterializedPath::fromString($model->materialized_path);
        $ancestorIds = $materializedPath->getAncestorIds();

        if (count($ancestorIds) === 0) {
            return [];
        }

        $models = ModelIngredient::whereIn('id', array_map(fn (IngredientId $id): int => $id->id, $ancestorIds))->get();

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
            name: $model->name,
            createdBy: new UserId($model->created_user_id),
            description: $model->description,
            strength: $model->strength,
            origin: $model->origin,
            color: $model->color ? Color::fromHexString($model->color) : null,
            calculatorId: $model->calculator_id ? new CalculatorId($model->calculator_id) : null,
            sugarContent: $model->sugar_g_per_ml,
            acidity: $model->acidity,
            distillery: $model->distillery,
            units: $model->units ? new Unit($model->units) : null,
            materializedPath: MaterializedPath::fromString($model->materialized_path),
            parentIngredientId: $model->parent_ingredient_id ? new IngredientId($model->parent_ingredient_id) : null,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );

        $ingredient->setId(new IngredientId($model->id));

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

        foreach ($model->images as $imageModel) {
            $ingredient->addImage(new ImageId($imageModel->id));
        }

        return $ingredient;
    }
}
