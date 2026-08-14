<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kami\Cocktail\Models\Concerns\HasAuthors;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplexIngredient extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ComplexIngredientFactory> */
    use HasFactory;
    use HasAuthors;

    public $timestamps = false;

    /**
     * @return array{amount: 'float', amount_max: 'float'}
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'amount_max' => 'float',
        ];
    }

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
