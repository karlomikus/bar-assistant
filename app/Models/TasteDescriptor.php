<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Kami\Cocktail\Models\Concerns\HasBarAwareScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TasteDescriptor extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\TasteDescriptorFactory> */
    use HasFactory;
    use HasBarAwareScope;

    protected $fillable = [
        'bar_id',
        'name',
        'normalized_name',
    ];

    /**
     * @return BelongsTo<Bar, $this>
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }

    /**
     * @return BelongsToMany<IngredientReview, $this>
     */
    public function ingredientReviews(): BelongsToMany
    {
        return $this->belongsToMany(IngredientReview::class, 'ingredient_review_taste_descriptor');
    }
}
