<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Ingredient;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

final class IngredientQueryFilter extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(Ingredient::query());

        $this->with('category', 'images')
            ->withCount('cocktails')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('category_id', 'ingredient_category_id'),
                AllowedFilter::exact('origin'),
                AllowedFilter::callback('on_shopping_list', function ($query) {
                    $usersList = $this->request->user()->shoppingLists->pluck('ingredient_id');
                    $query->whereIn('id', $usersList);
                }),
                AllowedFilter::callback('on_shelf', function ($query) {
                    $query->join('user_ingredients', 'user_ingredients.ingredient_id', '=', 'ingredients.id')->where('user_ingredients.user_id', $this->request->user()->id);
                }),
            ])
            ->defaultSort('name')
            ->allowedSorts('name', 'created_at');
    }
}
