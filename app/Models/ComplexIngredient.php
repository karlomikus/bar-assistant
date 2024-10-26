<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Model;
use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplexIngredient extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ComplexIngredientFactory> */
    use HasFactory;
    use HasAuthors;

    public $timestamps = false;

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function mainIngredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'main_ingredient_id');
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }
}
