<?php

declare(strict_types=1);

namespace Kami\Cocktail\Repository;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\DatabaseManager;

readonly class IngredientRepository
{
    public function __construct(private DatabaseManager $db)
    {
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
     * @return array<int, object>
     */
    public function getIngredientsForPossibleCocktails(int $barId, int $barMembershipId): array
    {
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
                            i.id NOT IN (
                                SELECT DISTINCT
                                    ui.ingredient_id
                                FROM
                                    user_ingredients ui
                                WHERE
                                    ui.bar_membership_id = :barMembershipId
                            )
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
                            ci.main_ingredient_id NOT IN (
                                SELECT DISTINCT
                                    ui.ingredient_id
                                FROM
                                    user_ingredients ui
                                WHERE
                                    ui.bar_membership_id = :barMembershipId
                            )
                            AND ci.ingredient_id IN (
                                SELECT DISTINCT
                                    ui.ingredient_id
                                FROM
                                    user_ingredients ui
                                WHERE
                                    ui.bar_membership_id = :barMembershipId
                            )
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

        return DB::select($rawQuery, ['barMembershipId' => $barMembershipId, 'barId' => $barId]);
    }
}
