<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'name',
    'normalized_name',
])]
class TasteDescriptor extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\TasteDescriptorFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<IngredientReview, $this>
     */
    public function ingredientReviews(): BelongsToMany
    {
        return $this->belongsToMany(IngredientReview::class, 'ingredient_review_taste_descriptor');
    }
}
