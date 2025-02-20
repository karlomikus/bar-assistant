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
        DB::beginTransaction();
        try {
            Reader::createFromPath($filepath)
                ->setHeaderOffset(0)
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
                    $ingredient->save();
                });
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();
    }
}
