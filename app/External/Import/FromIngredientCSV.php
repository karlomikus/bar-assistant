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
            $categories = DB::table('ingredient_categories')->select('id', DB::raw('LOWER(name) AS name'))->where('bar_id', $this->barId)->get();

            Reader::createFromPath($filepath)
                ->setHeaderOffset(0)
                ->each(function (array $record) use ($categories) {
                    $ingredientExternal = IngredientExternal::fromCSV($record);

                    $category = null;
                    if ($ingredientExternal->category) {
                        $category = $categories->firstWhere('name', mb_strtolower($ingredientExternal->category));
                    }

                    if (!$category && $ingredientExternal->category) {
                        $categoryId = DB::table('ingredient_categories')->insertGetId([
                            'name' => $ingredientExternal->category,
                            'bar_id' => $this->barId,
                            'created_at' => now(),
                            'updated_at' => null,
                        ]);
                        $category = (object) ['id' => $categoryId, 'name' => mb_strtolower($ingredientExternal->category)];
                        $categories->push($category);
                    }

                    $ingredient = new Ingredient();
                    $ingredient->bar_id = $this->barId;
                    $ingredient->ingredient_category_id = $category?->id;
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
