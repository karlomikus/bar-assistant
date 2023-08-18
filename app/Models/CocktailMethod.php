<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocktailMethod extends Model
{
    use HasFactory, HasBarAwareScope;

    public $timestamps = false;

    /**
     * @return HasMany<Cocktail>
     */
    public function cocktails(): HasMany
    {
        return $this->hasMany(Cocktail::class);
    }
}
