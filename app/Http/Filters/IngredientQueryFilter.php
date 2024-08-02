<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Ingredient;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Repository\IngredientRepository;

/**
 * @mixin \Kami\Cocktail\Models\Ingredient
 */
final class IngredientQueryFilter extends QueryBuilder
{
    public function __construct(IngredientRepository $ingredientQuery)
    {
        parent::__construct(Ingredient::query());

        $barMembership = $this->request->user()->getBarMembership(bar()->id);

        $this
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::custom('name', new FilterNameSearch()),
                AllowedFilter::beginsWithStrict('name_exact', 'name'),
                AllowedFilter::exact('category_id', 'ingredient_category_id'),
                AllowedFilter::partial('origin'),
                AllowedFilter::exact('created_user_id'),
                AllowedFilter::callback('on_shopping_list', function ($query, $value) use ($barMembership) {
                    if ($value === true) {
                        $query
                            ->join('user_shopping_lists', 'user_shopping_lists.ingredient_id', '=', 'ingredients.id')
                            ->where('user_shopping_lists.bar_membership_id', $barMembership->id);
                    }
                }),
                AllowedFilter::callback('on_shelf', function ($query, $value) use ($barMembership) {
                    if ($value === true) {
                        $query
                            ->join('user_ingredients', 'user_ingredients.ingredient_id', '=', 'ingredients.id')
                            ->where('user_ingredients.bar_membership_id', $barMembership->id);
                    }
                }),
                AllowedFilter::callback('strength_min', function ($query, $value) {
                    $query->where('strength', '>=', $value);
                }),
                AllowedFilter::callback('strength_max', function ($query, $value) {
                    $query->where('strength', '<=', $value);
                }),
                AllowedFilter::callback('main_ingredients', function ($query, $value) use ($ingredientQuery) {
                    if ($value === true) {
                        $ingredients = $ingredientQuery->getMainIngredientsOfCocktails(bar()->id);
                        $query->whereIn('ingredients.id', $ingredients->pluck('ingredient_id'));
                    }
                }),
                AllowedFilter::callback('complex', function ($query, $value) {
                    if ($value === true) {
                        $query->whereHas('ingredientParts');
                    }
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'strength',
                AllowedSort::callback('total_cocktails', function ($query, bool $descending) {
                    $direction = $descending ? 'DESC' : 'ASC';

                    $query
                        ->selectRaw('ingredients.*, COUNT(ci.ingredient_id) AS cocktails_count')
                        ->leftJoin('cocktail_ingredients AS ci', 'ci.ingredient_id', '=', 'ingredients.id')
                        ->groupBy('ingredients.id')
                        ->orderBy('cocktails_count', $direction);
                }),
            ])
            ->allowedIncludes(['parentIngredient', 'varieties', 'cocktails', 'cocktailIngredientSubstitutes', 'prices'])
            ->with('category', 'images')
            ->withCount('cocktails')
            ->filterByBar();
    }
}
