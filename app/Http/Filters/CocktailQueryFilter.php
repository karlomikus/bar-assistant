<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Cocktail;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

final class CocktailQueryFilter extends QueryBuilder
{
    public function __construct()
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
            ])
            ->defaultSort('name')
            ->allowedSorts('name', 'created_at');
    }
}
