<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Throwable;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Support\Unit;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Support\Color;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use BarAssistant\Domain\Support\ABV;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\Name;
use BarAssistant\Domain\Support\RecordTimestamps;
use Kami\Cocktail\Models\IngredientPrice as ModelIngredientPrice;

final class EloquentIngredientRepository implements IngredientRepository
{
    public function list(BarId $barId): array
    {
        $models = ModelIngredient::with('ingredientParts', 'prices.priceCategory')->where('bar_id', $barId->value)->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    public function findById(IngredientId $id): ?Ingredient
    {
        $model = ModelIngredient::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    /** @param IngredientId[] $ids */
    public function findMany(BarId $barId, array $ids): array
    {
        $models = ModelIngredient::with('ingredientParts', 'prices.priceCategory')->whereIn('id', array_map(fn(IngredientId $id) => $id->value, $ids))->where('bar_id', $barId->value)->get();

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
        $ingredientModel = ModelIngredient::with('ingredientParts', 'prices.priceCategory')->findOrNew($ingredient->getId()?->value);
        try {
            $ingredientModel->bar_id = $ingredient->getBarId()->value;
            $ingredientModel->name = $ingredient->getName();
            $ingredientModel->strength = $ingredient->getStrength()->toFloat();
            $ingredientModel->description = $ingredient->getDescription();
            $ingredientModel->origin = $ingredient->getOrigin();
            $ingredientModel->color = $ingredient->getColor()?->toHexString();
            $ingredientModel->created_user_id = $ingredient->getAuthors()->getCreatedBy()->value;
            $ingredientModel->created_at = $ingredient->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
            $ingredientModel->calculator_id = $ingredient->getCalculatorId()?->value;
            $ingredientModel->sugar_g_per_ml = $ingredient->getSugarContent();
            $ingredientModel->acidity = $ingredient->getAcidity();
            $ingredientModel->distillery = $ingredient->getDistillery();
            $ingredientModel->units = $ingredient->getUnits()?->value;
            $ingredientModel->materialized_path = $ingredient->getMaterializedPath()->toString();
            $ingredientModel->parent_ingredient_id = $ingredient->getParentIngredientId()?->value;
            if ($ingredient->getAuthors()->isUpdated()) {
                $ingredientModel->updated_user_id = $ingredient->getAuthors()->getUpdatedBy()?->value;
                $ingredientModel->updated_at = $ingredient->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
            }
            $ingredientModel->save();

            if ($ingredient->isTransient()) {
                $ingredient = $ingredient->setId(new IngredientId($ingredientModel->id));
            }

            $ingredientModel->ingredientParts()->delete();
            foreach ($ingredient->getIngredientParts() as $ingredientPartId) {
                $partModel = new ComplexIngredient();
                $partModel->ingredient_id = $ingredientPartId->value;
                $partModel->main_ingredient_id = $ingredientModel->id;
                $partModel->save();
            }

            $ingredientModel->prices()->delete();
            foreach ($ingredient->getPrices() as $price) {
                $ingredientPriceModel = new ModelIngredientPrice();
                $ingredientPriceModel->ingredient_id = $ingredientModel->id;
                $ingredientPriceModel->price_category_id = $price->getPriceCategoryId()->value;
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
            $imageModels = ModelImage::findOrFail(array_map(fn (ImageId $img): int => $img->value, $ingredient->getImages()));
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

        $models = ModelIngredient::with('ingredientParts', 'prices.priceCategory')->where('materialized_path', 'like', $ingredient->getMaterializedPath()->append($ingredient->getId())->toString() . '%')->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    public function delete(IngredientId $id): void
    {
        ModelIngredient::destroy($id->value);
    }

    public function findChildren(IngredientId $parentId): array
    {
        $models = ModelIngredient::with('ingredientParts', 'prices.priceCategory')->where('parent_ingredient_id', $parentId->value)->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = self::map($model);
        }

        return $ingredients;
    }

    public function findAncestors(IngredientId $descendantId): array
    {
        $model = ModelIngredient::with('ingredientParts', 'prices.priceCategory')->find($descendantId->value);

        if ($model === null) {
            return [];
        }

        $materializedPath = MaterializedPath::fromString($model->materialized_path);
        $ancestorIds = $materializedPath->getAncestorIds();

        if (count($ancestorIds) === 0) {
            return [];
        }

        $models = ModelIngredient::with('ingredientParts', 'prices.priceCategory')->whereIn('id', array_map(fn (IngredientId $id): int => $id->value, $ancestorIds))->get();

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
            name: Name::fromString($model->name),
            authors: Authors::createdBy(new UserId($model->created_user_id))->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
            description: $model->description,
            strength: ABV::from($model->strength ?? 0.0),
            origin: $model->origin,
            color: $model->color ? Color::fromHexString($model->color) : null,
            calculatorId: $model->calculator_id ? new CalculatorId($model->calculator_id) : null,
            sugarContent: $model->sugar_g_per_ml,
            acidity: $model->acidity,
            distillery: $model->distillery,
            units: $model->units ? new Unit($model->units) : null,
            materializedPath: MaterializedPath::fromString($model->materialized_path),
            parentIngredientId: $model->parent_ingredient_id ? new IngredientId($model->parent_ingredient_id) : null,
        );

        $ingredient->setId(new IngredientId($model->id));

        /** @var ComplexIngredient $part */
        foreach ($model->ingredientParts as $part) {
            $ingredient->addIngredientPart(self::map($part->ingredient));
        }

        /** @var ModelIngredientPrice $price */
        foreach ($model->prices as $price) {
            $ingredient->addPrice(
                priceCategoryId: new PriceCategoryId($price->price_category_id),
                price: $price->price,
                currency: $price->priceCategory->currency,
                amount: $price->amount,
                units: $price->units,
                description: $price->description,
            );
        }

        foreach ($model->images as $imageModel) {
            $ingredient->addImage(new ImageId($imageModel->id));
        }

        return $ingredient;
    }

    public function checkBarOwnership(BarId $barId, array $ingredientIds): bool
    {
        return DB::table('ingredients')
            ->where('bar_id', $barId->value)
            ->whereIn('id', array_map(static fn (IngredientId $id) => $id->value, $ingredientIds))
            ->count() > 0;
    }
}
