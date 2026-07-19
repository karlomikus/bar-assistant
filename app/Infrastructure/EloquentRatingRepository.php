<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RatingId;
use Kami\Cocktail\Models\Rating as Model;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Rating\RateableType;
use BarAssistant\Domain\Rating\RatingRepository;

final class EloquentRatingRepository implements RatingRepository
{
    public function findMemberRating(RateableId $cocktailId, RateableType $type, MemberId $memberId): ?Rating
    {
        $model = Model::where('rateable_id', $cocktailId->value)
            ->where('rateable_type', \Kami\Cocktail\Models\Cocktail::class)
            ->where('bar_membership_id', $memberId->value)
            ->first();

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Rating $rating): Rating
    {
        $modelToRate = Cocktail::findOrFail($rating->getRateableId());
        $ratingModel = $modelToRate->rate($rating->getValue()->value, $rating->getMemberId()->value);

        return self::map($ratingModel);
    }

    public function delete(RatingId $id): void
    {
        Model::destroy($id->value);
    }

    private static function map(Model $model): Rating
    {
        $rating = Rating::create(
            rateableId: new RateableId((int) $model->rateable_id),
            type: RateableType::Cocktail,
            memberId: new MemberId((int) $model->bar_membership_id),
            value: RatingValue::create((int) $model->rating),
        )->setId(new RatingId((int) $model->id));

        return $rating;
    }
}
