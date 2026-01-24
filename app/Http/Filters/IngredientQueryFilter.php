<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Ingredient;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Services\IngredientService;

/**
 * @extends \Spatie\QueryBuilder\QueryBuilder<Ingredient>
 */
final class IngredientQueryFilter extends QueryBuilder
{
    public function __construct(IngredientService $ingredientQuery)
    {
        parent::__construct(Ingredient::query());

        $barMembership = $this->request->user()->getBarMembership(bar()->id);

        $this
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::callback('parent_ingredient_id', function ($query, $value) use ($barMembership) {
                    if ($value === 'null') {
                        $query->onlyRootIngredients($barMembership->bar_id);
                    } else {
                        $query->where('parent_ingredient_id', $value);
                    }
                }),
                AllowedFilter::custom('name', new FilterNameSearch()),
                AllowedFilter::beginsWithStrict('name_exact', 'name'),
                AllowedFilter::partial('origin'),
                AllowedFilter::exact('created_user_id'),
                AllowedFilter::callback('on_shopping_list', function ($query, $value) use ($barMembership) {
                    if ($value === true) {
                        $query->onShoppingList($barMembership->id);
                    }
                }),
                AllowedFilter::callback('on_shelf', function ($query, $value) use ($barMembership) {
                    if ($value === true) {
                        $query->inUserShelf($barMembership->id);
                    }
                }),
                AllowedFilter::callback('bar_shelf', function ($query, $value) {
                    if ($value === true) {
                        $query->join('bar_ingredients', 'bar_ingredients.ingredient_id', '=', 'ingredients.id');
                    }
                }),
                AllowedFilter::callback('strength_min', function ($query, $value) {
                    $query->where('strength', '>=', $value);
                }),
                AllowedFilter::callback('strength_max', function ($query, $value) {
                    $query->where('strength', '<=', $value);
                }),
                AllowedFilter::callback('main_ingredients', function ($query, $value) use ($ingredientQuery, $barMembership) {
                    if ($value === true) {
                        $ingredients = $ingredientQuery->getMainIngredientsOfCocktails($barMembership->bar_id);
                        $query->whereIn('ingredients.id', $ingredients->pluck('ingredient_id'));
                    }
                }),
                AllowedFilter::callback('complex', function ($query, $value) {
                    if ($value === true) {
                        $query->whereHas('ingredientParts');
                    }
                }),
                AllowedFilter::callback('descendants_of', function ($query, $value) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    // TODO: Move to sqlite LIKE ANY when supported
                    foreach ($value as $id) {
                        $query->descendantsOf($id);
                    }
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'strength',
                AllowedSort::callback('potential_bar_shelf_cocktails', function ($query, bool $descending) use ($barMembership, $ingredientQuery) {
                    $direction = $descending ? 'DESC' : 'ASC';

                    $barIngredientIds = DB::table('bar_ingredients')
                        ->where('bar_id', $barMembership->bar_id)
                        ->pluck('ingredient_id')
                        ->toArray();
                    $complexIngredientIds = $ingredientQuery->resolveComplexIngredients($barIngredientIds);

                    $ingredientIds = array_merge($barIngredientIds, $complexIngredientIds);

                    $query->selectRaw('ingredients.*, COUNT(DISTINCT ci.cocktail_id) AS potential_cocktails_count')
                        ->leftJoin('cocktail_ingredients AS ci', function ($join) use ($ingredientIds) {
                            $join->on('ci.ingredient_id', '=', 'ingredients.id')
                                ->whereNotIn('ci.ingredient_id', $ingredientIds);
                        })
                        ->groupBy('ingredients.id')
                        ->orderBy('potential_cocktails_count', $direction);
                }),
                AllowedSort::field('total_cocktails', 'cocktails_count'),
            ])
            ->allowedIncludes(['parentIngredient', 'prices', 'ingredientParts', 'images', 'descendants', 'ancestors'])
            ->withCount('cocktails')
            ->filterByBar('ingredients')
            ->withInBarShelfColumn()
            ->with('bar.shelfIngredients');
    }
}
