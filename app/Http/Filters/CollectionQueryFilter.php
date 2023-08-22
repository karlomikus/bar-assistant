<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Filters;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Kami\Cocktail\Models\Collection as ItemsCollection;

final class CollectionQueryFilter extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(ItemsCollection::query());

        $barMembership = $this->request->user()->getBarMembership(bar()->id);

        $this
            ->where('bar_membership_id', $barMembership->id)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::exact('cocktail_id', 'cocktails.id'),
            ])
            ->defaultSort('name')
            ->allowedSorts('name', 'created_at')
            ->with('cocktails');
    }
}
