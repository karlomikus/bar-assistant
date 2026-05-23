<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use RuntimeException;
use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\BarIngredient;
use BarAssistant\Domain\Bar\BarInventory;
use Kami\Cocktail\Models\Bar as ModelBar;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Bar\BarInventoryRepository;
use BarAssistant\Domain\Bar\IngredientInventoryItem;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;

final class EloquentBarInventoryRepository implements BarInventoryRepository
{
    public function findByBarId(BarId $barId): ?BarInventory
    {
        $model = ModelBar::find($barId->value);
        if ($model === null) {
            return null;
        }

        $inStockIngredientIds = BarIngredient::query()
            ->where('bar_id', $barId->value)
            ->pluck('ingredient_id')
            ->all();

        return self::map(barId: $barId, inStockIngredientIds: $inStockIngredientIds);
    }

    public function save(BarInventory $barInventory): BarInventory
    {
        $model = ModelBar::find($barInventory->getBarId()->value);
        if ($model === null) {
            throw new RuntimeException('Cannot save inventory for non-existent bar.');
        }

        $inStockIngredientIds = array_map(
            static fn (IngredientInventoryItem $item): int => $item->ingredientId->value,
            $barInventory->getInStockIngredients(),
        );

        if ($inStockIngredientIds === []) {
            $model->shelfIngredients()->delete();

            return BarInventory::create($barInventory->getBarId());
        }

        $model->shelfIngredients()
            ->whereNotIn('ingredient_id', $inStockIngredientIds)
            ->delete();

        $existingIngredientIds = $model->shelfIngredients()
            ->pluck('ingredient_id')
            ->all();

        $newBarIngredients = [];
        foreach ($barInventory->getInStockIngredients() as $inStockIngredient) {
            if (!in_array($inStockIngredient->ingredientId->value, $existingIngredientIds, true)) {
                $barIngredientModel = new BarIngredient();
                $barIngredientModel->ingredient_id = $inStockIngredient->ingredientId->value;
                $newBarIngredients[] = $barIngredientModel;
            }
        }

        if (count($newBarIngredients) > 0) {
            $model->shelfIngredients()->saveMany($newBarIngredients);
        }

        return self::map(barId: $barInventory->getBarId(), inStockIngredientIds: $inStockIngredientIds);
    }

    /**
     * @param int[] $inStockIngredientIds
     */
    private static function map(BarId $barId, array $inStockIngredientIds): BarInventory
    {
        $barInventory = BarInventory::create($barId);

        foreach ($inStockIngredientIds as $ingredientId) {
            $barInventory->putIngredient(new IngredientId($ingredientId), IngredientInventoryStatus::InStock);
        }

        if ($inStockIngredientIds === []) {
            return $barInventory;
        }

        $placeholders = implode(',', array_fill(0, count($inStockIngredientIds), '?'));
        $complexIngredients = DB::table('complex_ingredients')
            ->select('main_ingredient_id as ingredient_id')
            ->join('ingredients', 'ingredients.id', 'complex_ingredients.main_ingredient_id')
            ->where('ingredients.bar_id', $barId->value)
            ->groupBy('main_ingredient_id')
            ->havingRaw('MIN(ingredient_id IN ('.$placeholders.')) = 1', $inStockIngredientIds)
            ->get();

        foreach ($complexIngredients as $complexIngredient) {
            if (!is_int($complexIngredient->ingredient_id) && !(is_string($complexIngredient->ingredient_id) && is_numeric($complexIngredient->ingredient_id))) {
                continue;
            }

            $complexIngredientId = (int) $complexIngredient->ingredient_id;
            if (in_array($complexIngredientId, $inStockIngredientIds, true)) {
                continue;
            }

            $barInventory->putIngredient(new IngredientId($complexIngredientId), IngredientInventoryStatus::Makeable);
        }

        return $barInventory;
    }
}
