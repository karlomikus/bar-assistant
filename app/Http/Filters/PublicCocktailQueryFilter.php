<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Cocktail;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;

/**
 * @extends \Spatie\QueryBuilder\QueryBuilder<Cocktail>
 */
final class PublicCocktailQueryFilter extends QueryBuilder
{
    public function __construct(Bar $bar)
    {
        parent::__construct(Cocktail::query());

        $this
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::custom('name', new FilterNameSearch()),
                AllowedFilter::partial('ingredient_name', 'ingredients.ingredient.name'),
                AllowedFilter::exact('ingredient_substitute_id', 'ingredients.substitutes.ingredient.id'),
                AllowedFilter::callback('ingredient_id', function ($query, $value) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    $query->where(function ($q) use ($value) {
                        $q->whereIn('ci.ingredient_id', $value)->orWhereIn('cis.ingredient_id', $value);
                    });
                }),
                AllowedFilter::exact('tag_id', 'tags.id'),
                AllowedFilter::exact('created_user_id'),
                AllowedFilter::exact('glass_id'),
                AllowedFilter::exact('cocktail_method_id'),
                AllowedFilter::callback('bar_shelf', function ($query, $value) use ($bar) {
                    if ($value === true) {
                        $query->whereIn('cocktails.id', $bar->getShelfCocktailsOnce());
                    }
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
                AllowedFilter::exact('parent_cocktail_id'),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'abv',
                'total_ingredients',
                AllowedSort::callback('random', function ($query) {
                    $query->inRandomOrder();
                }),
            ])
            ->allowedIncludes([
                'glass',
                'method',
                'user',
                'utensils',
                'images',
                'tags',
                'ingredients.ingredient',
            ])
            ->select('cocktails.*')
            ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'cocktails.id')
            ->leftJoin('cocktail_ingredient_substitutes AS cis', 'cis.cocktail_ingredient_id', '=', 'ci.id')
            ->leftJoin('bar_ingredients AS bi', function ($query) {
                $query->on('bi.ingredient_id', '=', 'ci.ingredient_id');
            })
            ->where('cocktails.bar_id', $bar->id)
            ->groupBy('cocktails.id')
            ->with(
                'bar.shelfIngredients',
                'ingredients.ingredient.bar',
                'tags',
                'ingredients.substitutes.ingredient',
                'glass',
                'method',
                'utensils',
                'images',
            );
    }
}
