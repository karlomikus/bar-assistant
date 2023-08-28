<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Glass;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

final class GlassQueryFilter extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(Glass::query());

        $this
            ->allowedFilters([
                AllowedFilter::partial('name'),
            ])
            ->defaultSort('name')
            ->allowedSorts('name', 'created_at')
            ->withCount('cocktails')
            ->filterByBar();
    }
}
