<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
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
    public function findMemberRating(RateableId $rateableId, RateableType $type, MemberId $memberId): ?Rating
    {
        $model = Model::where('rateable_id', $rateableId->value)
            ->where('rateable_type', self::modelClass($type))
            ->where('bar_membership_id', $memberId->value)
            ->first();

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Rating $rating): Rating
    {
        $modelClass = self::modelClass($rating->getType());
        $modelToRate = $modelClass::findOrFail($rating->getRateableId()->value);
        $ratingModel = $modelToRate->rate($rating->getValue()->value, $rating->getMemberId()->value);

        return self::map($ratingModel);
    }

    public function delete(RatingId $id): void
    {
        Model::destroy($id->value);
    }

    /**
     * @return class-string<Cocktail|Ingredient>
     */
    private static function modelClass(RateableType $type): string
    {
        return match ($type) {
            RateableType::Cocktail => Cocktail::class,
            RateableType::Ingredient => Ingredient::class,
        };
    }

    private static function typeFromModelClass(string $modelClass): RateableType
    {
        return match ($modelClass) {
            Ingredient::class => RateableType::Ingredient,
            default => RateableType::Cocktail,
        };
    }

    private static function map(Model $model): Rating
    {
        $rating = Rating::create(
            rateableId: new RateableId((int) $model->rateable_id),
            type: self::typeFromModelClass((string) $model->rateable_type),
            memberId: new MemberId((int) $model->bar_membership_id),
            value: RatingValue::create((float) $model->rating),
        )->setId(new RatingId((int) $model->id));

        return $rating;
    }
}
