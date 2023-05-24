<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Cocktail;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Services\CocktailService;

final class CocktailQueryFilter extends QueryBuilder
{
    public function __construct(CocktailService $cocktailService)
    {
        parent::__construct(Cocktail::query());

        $this->with('ingredients.ingredient', 'images', 'tags', 'method')
            ->withRatings($this->request->user()->id)
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
                    $query->whereIn('id', $cocktailService->getCocktailsByUserIngredients($this->request->user()->id));
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts('name', 'created_at');
    }
}
