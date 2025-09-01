<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\Cocktail;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\Enums\FilterOperator;

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
                AllowedFilter::partial('tag', 'tags.name'),
                AllowedFilter::partial('glass', 'glass.name'),
                AllowedFilter::partial('method', 'method.name'),
                AllowedFilter::callback('bar_shelf', function ($query, $value) use ($bar) {
                    if ($value === true) {
                        $query->whereIn('cocktails.id', $bar->getShelfCocktailsOnce());
                    }
                }),
                AllowedFilter::operator('abv', FilterOperator::DYNAMIC),
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'created_at',
                'abv',
                AllowedSort::callback('random', function ($query) {
                    $query->inRandomOrder();
                }),
            ])
            ->select('cocktails.*')
            ->leftJoin('cocktail_ingredients AS ci', 'ci.cocktail_id', '=', 'cocktails.id')
            ->leftJoin('cocktail_ingredient_substitutes AS cis', 'cis.cocktail_ingredient_id', '=', 'ci.id')
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
