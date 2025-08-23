<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services;

use Throwable;
use Illuminate\Log\LogManager;
use Kami\Cocktail\Models\Image;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\DatabaseManager;
use Kami\Cocktail\Models\IngredientPrice;
use Kami\Cocktail\Models\ComplexIngredient;
use Kami\Cocktail\OpenAPI\Schemas\IngredientRequest;
use Kami\Cocktail\Exceptions\ImagesNotAttachedException;
use Kami\Cocktail\Exceptions\IngredientValidationException;

final class IngredientService
{
    public function __construct(
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
    ) {
    }

    public function createIngredient(IngredientRequest $dto): Ingredient
    {
        DB::beginTransaction();

        try {
            if (blank($dto->name)) {
                throw new IngredientValidationException('Invalid ingredient name');
            }

            $ingredient = new Ingredient();
            $ingredient->bar_id = $dto->barId;
            $ingredient->name = $dto->name;
            $ingredient->strength = $dto->strength;
            $ingredient->description = $dto->description;
            $ingredient->origin = $dto->origin;
            $ingredient->color = $dto->color;
            $ingredient->created_user_id = $dto->userId;
            $ingredient->calculator_id = $dto->calculatorId;
            $ingredient->sugar_g_per_ml = $dto->sugarContent;
            $ingredient->acidity = $dto->acidity;
            $ingredient->distillery = $dto->distillery;
            $ingredient->units = $dto->units;
            $ingredient->save();

            if ($dto->parentIngredientId !== null) {
                $parentIngredient = Ingredient::findOrFail($dto->parentIngredientId);
                $ingredient->appendAsChildOf($parentIngredient);
            }

            foreach ($dto->complexIngredientParts as $ingredientPartId) {
                $part = new ComplexIngredient();
                $part->ingredient_id = $ingredientPartId;
                $part->main_ingredient_id = $ingredient->id;
                $part->save();
            }

            foreach ($dto->prices as $ingredientPriceDto) {
                $price = new IngredientPrice();
                $price->ingredient_id = $ingredient->id;
                $price->price_category_id = $ingredientPriceDto->priceCategoryId;
                $price->price = $ingredientPriceDto->price;
                $price->amount = $ingredientPriceDto->amount;
                $price->units = $ingredientPriceDto->units;
                $price->description = $ingredientPriceDto->description;
                $price->save();
            }
        } catch (Throwable $e) {
            $this->log->error('[INGREDIENT_SERVICE] ' . $e->getMessage());

            DB::rollBack();

            throw $e;
        }

        DB::commit();

        if (count($dto->images) > 0) {
            try {
                $imageModels = Image::findOrFail($dto->images);
                $ingredient->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImagesNotAttachedException();
            }
        }

        // Refresh model for response
        $ingredient->refresh();
        // Upsert scout index
        $ingredient->save();

        return $ingredient;
    }

    public function updateIngredient(int $id, IngredientRequest $dto): Ingredient
    {
        if ($dto->parentIngredientId === $id) {
            throw new IngredientValidationException('Parent ingredient is the same as the current ingredient!');
        }

        if (blank($dto->name)) {
            throw new IngredientValidationException('Invalid ingredient name');
        }

        DB::beginTransaction();

        try {
            $ingredient = Ingredient::findOrFail($id);
            $originalStrength = $ingredient->strength;
            $ingredient->name = $dto->name;
            $ingredient->strength = $dto->strength;
            $ingredient->description = $dto->description;
            $ingredient->origin = $dto->origin;
            $ingredient->color = $dto->color;
            $ingredient->updated_user_id = $dto->userId;
            $ingredient->updated_at = now();
            $ingredient->calculator_id = $dto->calculatorId;
            $ingredient->sugar_g_per_ml = $dto->sugarContent;
            $ingredient->acidity = $dto->acidity;
            $ingredient->distillery = $dto->distillery;
            $ingredient->units = $dto->units;
            $ingredient->save();

            if ($dto->parentIngredientId !== $ingredient->parent_ingredient_id) {
                if ($dto->parentIngredientId === null) {
                    $ingredient->appendAsChildOf(null);
                } else {
                    $parentIngredient = Ingredient::find($dto->parentIngredientId);
                    $ingredient->appendAsChildOf($parentIngredient);
                }
            }

            Model::unguard();
            $currentIngredientParts = [];
            foreach ($dto->complexIngredientParts as $complexPartId) {
                $currentIngredientParts[] = $complexPartId;
                $ingredient->ingredientParts()->updateOrCreate([
                    'ingredient_id' => $complexPartId
                ]);
            }
            $ingredient->ingredientParts()->whereNotIn('ingredient_id', $currentIngredientParts)->delete();
            Model::reguard();

            if (count($dto->prices) > 0) {
                $ingredient->prices()->delete();
                foreach ($dto->prices as $ingredientPriceDto) {
                    $price = new IngredientPrice();
                    $price->ingredient_id = $ingredient->id;
                    $price->price_category_id = $ingredientPriceDto->priceCategoryId;
                    $price->price = $ingredientPriceDto->price;
                    $price->amount = $ingredientPriceDto->amount;
                    $price->units = $ingredientPriceDto->units;
                    $price->description = $ingredientPriceDto->description;
                    $price->save();
                }
            }

        } catch (Throwable $e) {
            $this->log->error('[INGREDIENT_SERVICE] ' . $e->getMessage());
            DB::rollBack();

            throw $e;
        }

        DB::commit();

        if (count($dto->images) > 0) {
            try {
                $imageModels = Image::findOrFail($dto->images);
                $ingredient->attachImages($imageModels);
            } catch (Throwable $e) {
                throw new ImagesNotAttachedException();
            }
        }

        $this->log->info('[INGREDIENT_SERVICE] Ingredient updated with id:' . $ingredient->id);

        // Refresh model for response
        $ingredient->refresh();
        // Upsert scout index
        $ingredient->save();

        $ingredient->loadMissing('cocktails.ingredients.ingredient');

        if ($originalStrength !== $ingredient->strength) {
            $this->log->debug('[INGREDIENT_SERVICE] Updated ingredient strength, updating ' . $ingredient->cocktails->count() . ' cocktails.');
            $ingredient->cocktails->each(function (Cocktail $cocktail) {
                $cocktail->abv = $cocktail->getABV();
                $cocktail->save();
            });
        }

        if (!empty(config('scout.driver'))) {
            $ingredient->cocktails->each(fn ($cocktail) => $cocktail->searchable());
        }

        return $ingredient;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getMainIngredientsOfCocktails(int $barId): Collection
    {
        return $this->db->table('cocktail_ingredients')
            ->selectRaw('cocktail_ingredients.ingredient_id, COUNT(cocktail_ingredients.cocktail_id) AS cocktails')
            ->join('cocktails', 'cocktails.id', '=', 'cocktail_ingredients.cocktail_id')
            ->where('sort', 1)
            ->where('cocktails.bar_id', $barId)
            ->groupBy('cocktail_id')
            ->orderBy('cocktails.name', 'desc')
            ->get();
    }

    /**
     * @param array<int> $ingredientIds
     * @return array<int, object>
     */
    public function getIngredientsForPossibleCocktails(int $barId, array $ingredientIds): array
    {
        $placeholders = implode(',', array_map(fn ($id) => (int) $id, $ingredientIds));

        $rawQuery = "SELECT
            pi.ingredient_id as id,
            pi.ingredient_slug as slug,
            pi.ingredient_name as name,
            pi.potential_cocktails
        FROM
            (
                SELECT
                    mi.ingredient_id,
                    mi.ingredient_slug,
                    mi.ingredient_name,
                    COUNT(DISTINCT c.id) AS potential_cocktails
                FROM
                    (
                        -- Step 1: Ingredients the user doesn't have
                        SELECT
                            i.id AS ingredient_id,
                            i.slug AS ingredient_slug,
                            i.name AS ingredient_name
                        FROM
                            ingredients i
                        WHERE
                            i.id NOT IN (" . $placeholders . ")
                            and bar_id = :barId

                        EXCEPT

                        -- Step 2: Complex ingredients, user has ingredients in their shelf to make them
                        SELECT
                            ci.main_ingredient_id AS ingredient_id,
                            i.slug AS ingredient_slug,
                            i.name AS ingredient_name
                        FROM
                            complex_ingredients ci
                            JOIN ingredients i ON ci.main_ingredient_id = i.id
                        WHERE
                            ci.main_ingredient_id NOT IN (" . $placeholders . ")
                            AND ci.ingredient_id IN (" . $placeholders . ")
                            AND i.bar_id = :barId
                    ) mi
                    JOIN cocktail_ingredients ci ON mi.ingredient_id = ci.ingredient_id
                    JOIN cocktails c ON ci.cocktail_id = c.id
                GROUP BY
                    mi.ingredient_id,
                    mi.ingredient_slug,
                    mi.ingredient_name
            ) pi
        ORDER BY
            pi.potential_cocktails DESC
        LIMIT 10";

        return DB::select($rawQuery, ['barId' => $barId]);
    }

    public function rebuildMaterializedPath(int $barId): void
    {
        $startTime = microtime(true);
        $ingredients = DB::table('ingredients')
            ->where('bar_id', $barId)
            ->select('id', 'parent_ingredient_id')
            ->get()
            ->keyBy('id');

        $paths = [];
        foreach ($ingredients as $ingredient) {
            if ($ingredient->parent_ingredient_id === null) {
                $paths[$ingredient->id] = null;
                continue;
            }

            $path = '';
            $current = $ingredient;
            while ($current && $current->parent_ingredient_id !== null) {
                $path = $current->parent_ingredient_id . '/' . $path;
                $current = $ingredients->get($current->parent_ingredient_id);
            }
            $paths[$ingredient->id] = $path;
        }

        $cases = [];
        $bindings = [];
        $ids = [];

        foreach ($paths as $id => $path) {
            $cases[] = "WHEN ? THEN ?";
            $bindings[] = $id;
            $bindings[] = $path;
            $ids[] = $id;
        }

        if (empty($ids)) {
            return;
        }

        $ids = implode(',', $ids);
        $cases = implode(' ', $cases);

        DB::update("UPDATE ingredients SET materialized_path = CASE id {$cases} END WHERE id IN ({$ids})", $bindings);

        $endTime = microtime(true);
        $this->log->info('[INGREDIENT_SERVICE] Rebuilt materialized path for ingredients for bar ' . $barId . ' in ' . round($endTime - $startTime, 6) . ' seconds.');
    }

    /**
     * Return array of all ingredients that are part of the user's shelf
     * and can be used to create cocktails.
     *
     * Includes complex and variant ingredients.
     *
     * @param int $userId
     * @param int $barId
     * @return array<int>
     */
    public function getMemberIngredients(int $userId, int $barId): array
    {
        $userIngredientIds = $this->db->table('user_ingredients AS ui')
            ->select('ui.ingredient_id')
            ->join('bar_memberships AS bm', 'ui.bar_membership_id', '=', 'bm.id')
            ->where('bm.user_id', $userId)
            ->where('bm.bar_id', $barId)
            ->pluck('ingredient_id')
            ->toArray();

        if (empty($userIngredientIds)) {
            return [];
        }

        $descendantIngredientIds = $this->db->table('ingredients')
            ->select('descendant.id')
            ->join('ingredients AS descendant', function ($join) use ($barId) {
                $join->on(DB::raw("('/' || descendant.materialized_path || '/')"), 'LIKE', DB::raw("'%/' || ingredients.id || '/%'"))
                    ->where('descendant.bar_id', '=', $barId);
            })
            ->whereIn('ingredients.id', $userIngredientIds)
            ->where('ingredients.bar_id', $barId)
            ->pluck('id')
            ->toArray();

        $complexIngredients = $this->db->table('complex_ingredients AS ci')
            ->distinct()
            ->select('ci.main_ingredient_id')
            ->join('ingredients AS i_main', 'ci.main_ingredient_id', '=', 'i_main.id')
            ->whereIn('ci.id', function ($query) use ($userIngredientIds) {
                $query->select('ci_inner.id')
                    ->from('complex_ingredients AS ci_inner')
                    ->whereNotExists(function ($query) use ($userIngredientIds) {
                        $query->select('i_ingredient.id')
                            ->from('complex_ingredients AS ci_sub')
                            ->join('ingredients AS i_ingredient', 'ci_sub.ingredient_id', '=', 'i_ingredient.id')
                            ->whereColumn('ci_sub.main_ingredient_id', 'ci_inner.main_ingredient_id')
                            ->whereNotIn('i_ingredient.id', $userIngredientIds);
                    });
            })
            ->pluck('main_ingredient_id')
            ->toArray();

        return array_unique(array_merge(
            $descendantIngredientIds,
            $userIngredientIds,
            $complexIngredients
        ));
    }
}
