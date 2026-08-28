<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Cocktail;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Models\CocktailMethod;

/**
 * @extends \Spatie\QueryBuilder\QueryBuilder<CocktailMethod>
 */
final class CocktailMethodQueryFilter extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(CocktailMethod::query());

        $this
            ->allowedFilters([
                AllowedFilter::partial('name'),
            ])
            ->defaultSort('name')
            ->addSelect([
                'cocktails_count' => Cocktail::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('cocktail_methods.id', 'cocktails.cocktail_method_id')
                    ->where('cocktails.bar_id', bar()->id)
            ])
            ->filterByBar();
    }
}
