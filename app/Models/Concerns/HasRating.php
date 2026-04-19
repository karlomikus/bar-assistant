<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Concerns;

use Kami\Cocktail\Models\Rating;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasRating
{
    /**
     * @return MorphMany<Rating, $this>
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function rate(int $ratingValue, int $barMembershipId): Rating
    {
        $rating = $this->ratings()->where('bar_membership_id', $barMembershipId)->first();

        if (!$rating) {
            $rating = new Rating();
        }

        $rating->rating = $ratingValue;
        $rating->bar_membership_id = $barMembershipId;

        $this->ratings()->save($rating);

        return $rating;
    }

    public function totalRatedCount(): int
    {
        return $this->ratings->count();
    }

    public function deleteBarMembershipRating(int $barMembershipId): void
    {
        $this->ratings()->where('bar_membership_id', $barMembershipId)->delete();
    }

    public function deleteRatings(): void
    {
        $this->ratings()->delete();
    }
}
