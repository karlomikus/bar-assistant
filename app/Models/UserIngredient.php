<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserIngredient extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UserIngredientFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return BelongsTo<Ingredient, UserIngredient>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<BarMembership, UserIngredient>
     */
    public function barMembership(): BelongsTo
    {
        return $this->belongsTo(BarMembership::class);
    }
}
