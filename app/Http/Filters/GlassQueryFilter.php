<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Glass;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends \Spatie\QueryBuilder\QueryBuilder<Glass>
 */
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
            ->addSelect([
                'cocktails_count' => Cocktail::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('glasses.id', 'cocktails.glass_id')
                    ->where('cocktails.bar_id', bar()->id)
            ])
            ->with('images')
            ->filterByBar();
    }
}
