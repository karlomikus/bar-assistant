<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Builder;

trait HasBarAwareScope
{
    public function scopeBarAware(Builder $query): Builder
    {
        return $query->where('bar_id', bar()->id);
    }
}
