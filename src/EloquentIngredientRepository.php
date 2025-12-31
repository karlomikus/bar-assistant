<?php

declare(strict_types=1);

namespace BarAssistant;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Domain\Support\RecordTimestamps;
use BarAssistant\Domain\User\UserId;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\Models\IngredientPrice;

final class EloquentIngredientRepository implements IngredientRepository
{
    public function find(IngredientId $id): ?Ingredient
    {
        $model = ModelIngredient::findOrFail($id->id);

        return $this->map($model);
    }

    /** @param IngredientId[] $ids */
    public function findMany(BarId $barId, array $ids): array
    {
        $models = ModelIngredient::whereIn('id', array_map(fn(IngredientId $id) => $id->id, $ids))->where('bar_id', $barId->id)->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = $this->map($model);
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
                $ingredientPriceModel = new IngredientPrice();
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

    public function findDescendants(IngredientId $ancestorId): array
    {
        $models = ModelIngredient::where('materialized_path', 'LIKE', $ancestorId->id . '/%')->get();

        $ingredients = [];
        /** @var ModelIngredient $model */
        foreach ($models as $model) {
            $ingredients[] = $this->map($model);
        }

        return $ingredients;
    }

    private function map(ModelIngredient $model): Ingredient
    {
        $ingredient = new Ingredient(
            barId: new BarId($model->bar_id),
            name: $model->name,
            description: $model->description,
            strength: $model->strength,
            origin: $model->origin,
            authors: Authors::createdBy(new UserId($model->created_user_id))->updatedBy($model->updated_user_id ? new UserId($model->updated_user_id) : null),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at ? $model->updated_at->toDateTimeImmutable() : null),
            color: $model->color ? Color::fromHexString($model->color) : null,
        );

        $ingredient
            ->setId(new IngredientId($model->id))
            ->setParentIngredient($model->parent_ingredient_id ? $this->map(ModelIngredient::find($model->parent_ingredient_id)) : null)
            ->setMaterializedPath(MaterializedPath::fromString($model->materialized_path));

        /** @var ComplexIngredient $part */
        foreach ($model->ingredientParts as $part) {
            $ingredient->addIngredientPart($this->map($part->ingredient));
        }

        return $ingredient;
    }
}
