<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Concerns;

use Kami\Cocktail\Models\Rating;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasRating
{
    /**
     * @return MorphMany<Rating>
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function rate(int $ratingValue, int $userId): Rating
    {
        $rating = $this->ratings()->where('user_id', $userId)->first();

        if (!$rating) {
            $rating = new Rating();
        }

        $rating->rating = $ratingValue;
        $rating->user_id = $userId;

        $this->ratings()->save($rating);

        return $rating;
    }

    public function totalRatedCount(): int
    {
        return $this->ratings()->count();
    }

    public function deleteUserRating(int $userId): void
    {
        $this->ratings()->where('user_id', $userId)->delete();
    }

    public function deleteRatings(): void
    {
        $this->ratings()->delete();
    }
}
