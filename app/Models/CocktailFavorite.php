<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocktailFavorite extends Model
{
    use HasFactory;

    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
    }
}
