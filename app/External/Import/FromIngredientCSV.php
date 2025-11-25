<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use Throwable;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\External\Model\Ingredient as IngredientExternal;

class FromIngredientCSV
{
    public function __construct(
        private readonly int $barId,
        private readonly int $userId,
    ) {
    }

    public function process(
        string $filepath,
        DuplicateActionsEnum $duplicateAction = DuplicateActionsEnum::None,
        string $imageDirectoryBasePath = '',
    ): void {
        $existingIngredientNames = Ingredient::where('bar_id', $this->barId)
            ->pluck('name')
            ->map(fn (string $name) => mb_strtolower($name))
            ->toArray();

        DB::beginTransaction();
        try {
            Reader::createFromPath($filepath)
                ->setHeaderOffset(0)
                ->filter(function (array $record) use ($existingIngredientNames, $duplicateAction) {
                    $record = array_change_key_case($record, CASE_LOWER);

                    $ingredientNameLower = mb_strtolower($record['name'] ?? '');
                    if ($duplicateAction === DuplicateActionsEnum::Skip && in_array($ingredientNameLower, $existingIngredientNames, true)) {
                        return false;
                    }

                    return true;
                })
                ->each(function (array $record) {
                    $ingredientExternal = IngredientExternal::fromCSV($record);

                    $ingredient = new Ingredient();
                    $ingredient->bar_id = $this->barId;
                    $ingredient->name = $ingredientExternal->name;
                    $ingredient->strength = $ingredientExternal->strength;
                    $ingredient->description = $ingredientExternal->description;
                    $ingredient->origin = $ingredientExternal->origin;
                    $ingredient->color = $ingredientExternal->color;
                    $ingredient->created_user_id = $this->userId;
                    $ingredient->created_at = now();
                    $ingredient->sugar_g_per_ml = $ingredientExternal->sugarContent;
                    $ingredient->acidity = $ingredientExternal->acidity;
                    $ingredient->distillery = $ingredientExternal->distillery;
                    $ingredient->save();
                });
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
    }
}
