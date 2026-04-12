<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Application\Exception\ApplicationServiceException;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RatingId;
use Kami\Cocktail\Models\Rating as Model;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Rating\RateableType;
use BarAssistant\Domain\Rating\RatingRepository;
use BarAssistant\Domain\User\UserId;
use Kami\Cocktail\Models\Cocktail;

final class EloquentRatingRepository implements RatingRepository
{
    public function findUserRating(RateableId $cocktailId, RateableType $type, UserId $userId): ?Rating
    {
        $model = Model::where('rateable_id', $cocktailId->value)
            ->where('rateable_type', \Kami\Cocktail\Models\Cocktail::class)
            ->where('user_id', $userId->value)
            ->first();

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Rating $rating): Rating
    {
        if ($rating->getType() !== RateableType::Cocktail) {
            throw new ApplicationServiceException('Unsupported rateable type');
        }

        $modelToRate = Cocktail::findOrFail($rating->getRateableId());
        $ratingModel = $modelToRate->rate($rating->getValue()->value, $rating->getUserId()->value);

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
            userId: new UserId((int) $model->user_id),
            value: RatingValue::create((int) $model->rating),
        )->setId(new RatingId((int) $model->id));
    
        return $rating;
    }
}
