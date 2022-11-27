<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CocktailFavorite extends Model
{
    use HasFactory;

    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
    }
}
