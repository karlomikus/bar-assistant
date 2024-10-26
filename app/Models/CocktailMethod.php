<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocktailMethod extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CocktailMethodFactory> */
    use HasFactory;
    use HasBarAwareScope;
    use HasAuthors;

    /**
     * @return HasMany<Cocktail, $this>
     */
    public function cocktails(): HasMany
    {
        return $this->hasMany(Cocktail::class);
    }

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }
}
