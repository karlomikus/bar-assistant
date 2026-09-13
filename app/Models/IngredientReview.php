<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property \Carbon\Carbon $created_at
 * @property float|null $rating
 */
class IngredientReview extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\IngredientReviewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<BarMembership, $this>
     */
    public function barMembership(): BelongsTo
    {
        return $this->belongsTo(BarMembership::class);
    }

    /**
     * @return BelongsToMany<TasteDescriptor, $this>
     */
    public function tasteDescriptors(): BelongsToMany
    {
        return $this->belongsToMany(TasteDescriptor::class, 'ingredient_review_taste_descriptor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public static function queryReviewsForIngredient(int $ingredientId): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()
            ->with('barMembership.user', 'tasteDescriptors')
            ->select('ingredient_reviews.*')
            ->addSelect([
                'rating' => Rating::select('rating')
                    ->whereColumn('rateable_id', 'ingredient_reviews.ingredient_id')
                    ->whereColumn('rateable_type', Ingredient::class)
                    ->whereColumn('bar_membership_id', 'ingredient_reviews.bar_membership_id'),
            ])
            ->where('ingredient_reviews.ingredient_id', $ingredientId)
            ->orderBy('ingredient_reviews.created_at', 'desc')
            ->orderBy('ingredient_reviews.id', 'desc');
    }
}
