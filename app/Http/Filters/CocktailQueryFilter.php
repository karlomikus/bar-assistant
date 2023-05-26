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
                AllowedFilter::partial('name'),
                AllowedFilter::partial('ingredient_name', 'ingredients.ingredient.name'),
                AllowedFilter::exact('ingredient_id', 'ingredients.ingredient.id'),
                AllowedFilter::exact('tag_id', 'tags.id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('glass_id'),
                AllowedFilter::exact('cocktail_method_id'),
                AllowedFilter::callback('favorites', function ($query) {
                    $query->userFavorites($this->request->user()->id);
                }),
                AllowedFilter::callback('on_shelf', function ($query) use ($cocktailService) {
                    $query->whereIn('cocktails.id', $cocktailService->getCocktailsByUserIngredients($this->request->user()->id));
                }),
                AllowedFilter::callback('is_public', function ($query) {
                    $query->whereNotNull('public_id');
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                AllowedSort::callback('favorited_at', function ($query, bool $descending) {
                    $direction = $descending ? 'DESC' : 'ASC';

                    $query->leftJoin('cocktail_favorites AS cf', 'cf.cocktail_id', '=', 'cocktails.id')
                        ->orderBy('cf.updated_at', $direction);
                })
            ])
            ->with('ingredients.ingredient', 'images', 'tags', 'method')
            ->withRatings($this->request->user()->id);
    }
}
