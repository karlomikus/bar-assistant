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

        $this
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('ingredient_name', 'ingredients.ingredient.name'),
                AllowedFilter::exact('ingredient_id', 'ingredients.ingredient.id'),
                AllowedFilter::exact('tag_id', 'tags.id'),
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
                        $query->whereIn('cocktails.id', $cocktailService->getCocktailsByUserIngredients($this->request->user()->id));
                    }
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

                    $query
                        ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'cocktails.id')
                        ->whereIn('ci.ingredient_id', $value)
                        ->where('sort', '=', 1);
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'average_rating',
                'user_rating',
                'abv',
                AllowedSort::callback('favorited_at', function ($query, bool $descending) {
                    $direction = $descending ? 'DESC' : 'ASC';

                    $query->leftJoin('cocktail_favorites AS cf', 'cf.cocktail_id', '=', 'cocktails.id')
                        ->orderBy('cf.updated_at', $direction);
                }),
                AllowedSort::callback('missing_ingredients', function ($query, bool $descending) {
                    $direction = $descending ? 'DESC' : 'ASC';

                    $query
                        ->selectRaw('cocktails.*, COUNT(ci.ingredient_id) - COUNT(ui.ingredient_id) AS missing_ingredients')
                        ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'cocktails.id')
                        ->leftJoin('user_ingredients AS ui', function ($query) {
                            $query->on('ui.ingredient_id', '=', 'ci.ingredient_id')->where('ui.user_id', $this->request->user()->id);
                        })
                        ->groupBy('cocktails.id')
                        ->orderBy('missing_ingredients', $direction);
                }),
            ])
            ->with('ingredients.ingredient', 'images', 'tags', 'method', 'user')
            ->withRatings($this->request->user()->id);
    }
}
