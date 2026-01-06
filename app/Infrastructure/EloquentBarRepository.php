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

final class EloquentBarRepository implements BarRepository
{
    public function findById(BarId $id): ?Bar
    {
        $model = ModelBar::find($id->id);

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
            fn (IngredientInventoryItem $item) => $item->ingredientId->id,
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
            name: $model->name,
            inventory: new BarInventory($barIngredients),
        )->setId(new BarId($model->id));

        return $bar;
    }
}
