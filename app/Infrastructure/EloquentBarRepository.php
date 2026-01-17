<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Bar\BarInventory;
use Kami\Cocktail\Models\Bar as ModelBar;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Bar\IngredientInventoryItem;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;
use BarAssistant\Domain\Common\Name;
use Kami\Cocktail\Models\BarIngredient;

final class EloquentBarRepository implements BarRepository
{
    public function save(Bar $bar): Bar
    {
        $model = ModelBar::findOrNew($bar->getId()?->value);

        $model->name = (string) $bar->getName();
        $model->save();

        // Get current ingredient IDs from the domain model
        $inStockIngredientIds = array_map(
            fn (IngredientInventoryItem $item) => $item->ingredientId->value,
            $bar->getInStockIngredients()
        );

        // Remove ingredients that are no longer in stock
        $model->shelfIngredients()
            ->whereNotIn('ingredient_id', $inStockIngredientIds)
            ->delete();

        // Get existing ingredient IDs to avoid duplicates
        $existingIngredientIds = $model->shelfIngredients()
            ->pluck('ingredient_id')
            ->toArray();

        // Add new ingredients
        $newBarIngredients = [];
        foreach ($bar->getInStockIngredients() as $inStockIngredient) {
            if (!in_array($inStockIngredient->ingredientId->value, $existingIngredientIds, true)) {
                $barIngredientModel = new BarIngredient();
                $barIngredientModel->ingredient_id = $inStockIngredient->ingredientId->value;
                $newBarIngredients[] = $barIngredientModel;
            }
        }

        if (count($newBarIngredients) > 0) {
            $model->shelfIngredients()->saveMany($newBarIngredients);
        }

        return self::map($model);
    }

    public function findById(BarId $id): ?Bar
    {
        $model = ModelBar::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    private static function map(ModelBar $model): Bar
    {
        $model->load('shelfIngredients.ingredient');

        $barIngredients = [];
        foreach ($model->shelfIngredients as $barShelfIngredient) {
            $barIngredients[] = new IngredientInventoryItem(
                ingredientId: new IngredientId($barShelfIngredient->ingredient_id),
                ingredientStatus: IngredientInventoryStatus::InStock,
            );
        }

        $ingredientIds = array_map(
            fn (IngredientInventoryItem $item) => $item->ingredientId->value,
            $barIngredients
        );
        $placeholders = implode(',', array_fill(0, count($ingredientIds), '?'));
        $complexIngredients = DB::table('complex_ingredients')
            ->select('main_ingredient_id as ingredient_id')
            ->join('ingredients', 'ingredients.id', 'complex_ingredients.main_ingredient_id')
            ->where('ingredients.bar_id', $model->id)
            ->groupBy('main_ingredient_id')
            ->havingRaw('MIN(ingredient_id IN ('.$placeholders.')) = 1', [$ingredientIds])
            ->get();

        foreach ($complexIngredients as $ing) {
            $barIngredients[] = new IngredientInventoryItem(
                ingredientId: new IngredientId($ing->ingredient_id),
                ingredientStatus: IngredientInventoryStatus::Makeable,
            );
        }

        $bar = new Bar(
            name: Name::fromString($model->name),
            ingredientInventory: $barIngredients,
        )->setId(new BarId($model->id));

        return $bar;
    }
}
