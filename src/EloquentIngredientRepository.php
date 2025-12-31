<?php

declare(strict_types=1);

namespace BarAssistant;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientRepository;
use Kami\Cocktail\Models\Ingredient as ModelIngredient;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\Support\Color;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\ComplexIngredient;

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

        $model = ModelIngredient::findOrNew($ingredient->getId()?->id);

        try {
            $model->bar_id = $ingredient->getBarId()->id;
            $model->name = $ingredient->getName();
            $model->strength = $ingredient->getStrength();
            $model->description = $ingredient->getDescription();
            $model->origin = $ingredient->getOrigin();
            $model->color = $ingredient->getColor()?->toHexString();
            $model->created_user_id = 1;
            $model->calculator_id = null;
            $model->sugar_g_per_ml = null;
            $model->acidity = null;
            $model->distillery = null;
            $model->units = null;
            $model->materialized_path = $ingredient->getMaterializedPath()->toString();
            $model->parent_ingredient_id = $ingredient->getParentIngredientId()?->id;
            $model->save();

            foreach ($ingredient->getIngredientParts() as $ingredientPartId) {
                $part = new ComplexIngredient();
                $part->ingredient_id = $ingredientPartId->id;
                $part->main_ingredient_id = $model->id;
                $part->save();
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        DB::commit();

        if ($ingredient->isTransient()) {
            $ingredient = $ingredient->setId(new IngredientId($model->id));
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
