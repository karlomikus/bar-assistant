<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Builder;

trait HasBarAwareScope
{
    /**
     * Filter query by current Bar context
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function scopeBarAware(Builder $query): Builder
    {
        return $query->where('bar_id', bar()->id);
    }
}
