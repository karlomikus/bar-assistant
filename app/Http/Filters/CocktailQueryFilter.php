<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Cocktail;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Services\CocktailService;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
final class CocktailQueryFilter extends QueryBuilder
{
    public function __construct(CocktailService $cocktailService)
    {
        parent::__construct(Cocktail::query());

        $barMembership = $this->request->user()->getBarMembership(bar()->id);

        $this
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('ingredient_name', 'ingredients.ingredient.name'),
                AllowedFilter::exact('ingredient_id', 'ingredients.ingredient.id'),
                AllowedFilter::exact('tag_id', 'tags.id'),
                AllowedFilter::exact('collection_id', 'collections.id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('glass_id'),
                AllowedFilter::exact('cocktail_method_id'),
                AllowedFilter::callback('favorites', function ($query, $value) {
                    if ($value === true) {
                        $query->userFavorites($this->request->user()->id);
                    }
                }),
                AllowedFilter::callback('on_shelf', function ($query, $value) use ($cocktailService) {
                    if ($value === true) {
                        $query->whereIn('cocktails.id', $cocktailService->getCocktailsByIngredients(
                            $this->request->user()->shelfIngredients->pluck('ingredient_id')->toArray()
                        ));
                    }
                }),
                AllowedFilter::callback('shelf_ingredients', function ($query, $value) use ($cocktailService) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $query->whereIn('cocktails.id', $cocktailService->getCocktailsByIngredients($value));
                }),
                AllowedFilter::callback('is_public', function ($query, $value) {
                    if ($value === true) {
                        $query->whereNotNull('public_id');
                    }
                }),
                AllowedFilter::callback('user_rating_min', function ($query, $value) {
                    $query->where('user_rating', '>=', (int) $value);
                }),
                AllowedFilter::callback('user_rating_max', function ($query, $value) {
                    $query->where('user_rating', '<=', (int) $value);
                }),
                AllowedFilter::callback('abv_min', function ($query, $value) {
                    $query->where('abv', '>=', $value);
                }),
                AllowedFilter::callback('abv_max', function ($query, $value) {
                    $query->where('abv', '<=', $value);
                }),
                AllowedFilter::callback('main_ingredient_id', function ($query, $value) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $query->whereIn('ci.ingredient_id', $value)->where('sort', '=', 1);
                }),
                AllowedFilter::callback('total_ingredients', function ($query, $value) {
                    $query->having('total_ingredients', '>=', (int) $value);
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'average_rating',
                'user_rating',
                'abv',
                'total_ingredients',
                'missing_ingredients',
                AllowedSort::callback('favorited_at', function ($query, bool $descending) use ($barMembership) {
                    $direction = $descending ? 'DESC' : 'ASC';

                    $query->leftJoin('cocktail_favorites AS cf', 'cf.cocktail_id', '=', 'cocktails.id')
                        ->where('cf.bar_membership_id', $barMembership->id)
                        ->orderBy('cf.updated_at', $direction);
                }),
            ])
            ->allowedIncludes(['glass', 'method', 'user', 'collections', 'notes', 'navigation'])
            ->with('ingredients.ingredient', 'images', 'tags')
            ->selectRaw('cocktails.*, COUNT(ci.cocktail_id) AS total_ingredients, COUNT(ci.ingredient_id) - COUNT(ui.ingredient_id) AS missing_ingredients')
            ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'cocktails.id')
            ->leftJoin('user_ingredients AS ui', function ($query) use ($barMembership) {
                $query->on('ui.ingredient_id', '=', 'ci.ingredient_id')->where('ui.bar_membership_id', $barMembership->id);
            })
            ->groupBy('cocktails.id')
            ->barAware()
            ->withRatings($this->request->user()->id);
    }
}
