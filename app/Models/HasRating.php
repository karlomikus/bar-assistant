<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

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

    public function getAverageRating(): int
    {
        // Query optimization step
        if (isset($this->averageRating)) {
            return (int) round($this->averageRating ?? 0);
        }

        return (int) round($this->ratings()->avg('rating') ?? 0);
    }

    public function totalRatedCount(): int
    {
        return $this->ratings()->count();
    }

    public function getUserRating(?int $userId = null): ?int
    {
        if ($userId && $this->userRating === -1) {
            return $this->ratings()->where('user_id', $userId)->first()?->rating ?? null;
        }

        return $this->userRating ?? null;
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
