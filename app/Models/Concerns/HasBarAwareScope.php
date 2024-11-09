<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasBarAwareScope
{
    /**
     * Filter query by current Bar context
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function scopeFilterByBar(Builder $query, ?string $alias = null): Builder
    {
        $col = 'bar_id';
        if ($alias !== null) {
            $col = $alias . '.' . $col;
        }

        return $query->where($col, bar()->id);
    }
}
