<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property \Carbon\Carbon $created_at
 * @property float|null $rating
 * @property int $author_user_id
 * @property string $author_name
 */
class CocktailReview extends BaseModel
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\CocktailReviewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Cocktail, $this>
     */
    public function cocktail(): BelongsTo
    {
        return $this->belongsTo(Cocktail::class);
    }

    /**
     * @return BelongsTo<BarMembership, $this>
     */
    public function barMembership(): BelongsTo
    {
        return $this->belongsTo(BarMembership::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public static function queryReviewsForCocktail(int $cocktailId): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()
            ->with('barMembership.user')
            ->select('cocktail_reviews.*')
            ->addSelect([
                'rating' => Rating::select('rating')
                    ->whereColumn('rateable_id', 'cocktail_reviews.cocktail_id')
                    ->whereColumn('rateable_type', Cocktail::class)
                    ->whereColumn('bar_membership_id', 'cocktail_reviews.bar_membership_id'),
            ])
            ->where('cocktail_reviews.cocktail_id', $cocktailId)
            ->orderBy('cocktail_reviews.created_at', 'desc')
            ->orderBy('cocktail_reviews.id', 'desc');
    }
}
