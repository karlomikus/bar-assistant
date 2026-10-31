<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Illuminate\Database\DatabaseManager;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\ComplexIngredientResolver;

final class EloquentComplexIngredientResolver implements ComplexIngredientResolver
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function findMakeableMainIngredientIds(array $ingredientIds): array
    {
        if (count($ingredientIds) === 0) {
            return [];
        }

        $ids = array_map(static fn (IngredientId $ingredientId): int => $ingredientId->value, $ingredientIds);

        return $this->db->table('complex_ingredients AS ci')
            ->distinct()
            ->select('ci.main_ingredient_id')
            ->join('ingredients AS i_main', 'ci.main_ingredient_id', '=', 'i_main.id')
            ->whereIn('ci.id', function ($query) use ($ids) {
                $query->select('ci_inner.id')
                    ->from('complex_ingredients AS ci_inner')
                    ->whereNotExists(function ($query) use ($ids) {
                        $query->select('i_ingredient.id')
                            ->from('complex_ingredients AS ci_sub')
                            ->join('ingredients AS i_ingredient', 'ci_sub.ingredient_id', '=', 'i_ingredient.id')
                            ->whereColumn('ci_sub.main_ingredient_id', 'ci_inner.main_ingredient_id')
                            ->whereNotIn('i_ingredient.id', $ids);
                    });
            })
            ->pluck('main_ingredient_id')
            ->map(static fn ($mainIngredientId): IngredientId => new IngredientId((int) $mainIngredientId))
            ->values()
            ->toArray();
    }
}
