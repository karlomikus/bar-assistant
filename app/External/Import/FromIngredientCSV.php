<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use League\Csv\Reader;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use ($filepath) {
            Reader::createFromPath($filepath)
                ->setHeaderOffset(0)
                ->each(function (array $record) {
                    $ingredientExternal = IngredientExternal::fromCSV($record);

                    DB::table('ingredients')->insert([
                        'bar_id' => $this->barId,
                        'slug' => Str::slug($ingredientExternal->name) . '-' . $this->barId,
                        'name' => $ingredientExternal->name,
                        'strength' => $ingredientExternal->strength,
                        'description' => $ingredientExternal->description,
                        'origin' => $ingredientExternal->origin,
                        'color' => $ingredientExternal->color,
                        'created_user_id' => $this->userId,
                        'created_at' => now(),
                        'updated_at' => null,
                    ]);
                });
        });
    }
}
