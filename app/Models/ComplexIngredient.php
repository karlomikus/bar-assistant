<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplexIngredient extends Model
{
    use HasAuthors;

    public $timestamps = false;

    /**
     * @return BelongsTo<Ingredient, ComplexIngredient>
     */
    public function mainIngredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'main_ingredient_id');
    }

    /**
     * @return BelongsTo<Ingredient, ComplexIngredient>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }
}
