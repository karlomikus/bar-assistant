<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use DateTimeImmutable;
use BarAssistant\Domain\Bar\Rating;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\RatingId;
use Kami\Cocktail\Models\Rating as Model;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Bar\RatingRepository;

final class EloquentRatingRepository implements RatingRepository
{
    public function findById(RatingId $id): ?Rating
    {
        $model = Model::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function findByCocktailAndMember(CocktailId $cocktailId, MemberId $memberId): ?Rating
    {
        $model = Model::where('rateable_id', $cocktailId->value)
            ->where('rateable_type', \Kami\Cocktail\Models\Cocktail::class)
            ->where('user_id', $memberId->value)
            ->first();

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    /**
     * @return Rating[]
     */
    public function findByMember(MemberId $memberId): array
    {
        $models = Model::where('user_id', $memberId->value)
            ->where('rateable_type', \Kami\Cocktail\Models\Cocktail::class)
            ->get();

        return $models->map(fn (Model $model) => self::map($model))->all();
    }

    /**
     * @return Rating[]
     */
    public function findByCocktail(CocktailId $cocktailId): array
    {
        $models = Model::where('rateable_id', $cocktailId->value)
            ->where('rateable_type', \Kami\Cocktail\Models\Cocktail::class)
            ->get();

        return $models->map(fn (Model $model) => self::map($model))->all();
    }

    public function save(Rating $rating): Rating
    {
        $model = Model::findOrNew($rating->getId()?->value);

        if ($rating->isTransient()) {
            $model->rateable_id = $rating->getCocktailId()->value;
            $model->rateable_type = \Kami\Cocktail\Models\Cocktail::class;
            $model->user_id = $rating->getMemberId()->value;
        }

        $model->rating = $rating->getValue();
        $model->save();

        $rating->setId(new RatingId((int) $model->id));

        return $rating;
    }

    public function delete(RatingId $id): void
    {
        Model::destroy($id->value);
    }

    /**
     * @return CocktailId[]
     */
    public function findLowRatedCocktailIds(MemberId $memberId, int $maxValue = 2): array
    {
        $models = Model::select('rateable_id')
            ->where('user_id', $memberId->value)
            ->where('rateable_type', \Kami\Cocktail\Models\Cocktail::class)
            ->where('rating', '<=', $maxValue)
            ->distinct()
            ->get();

        return $models->map(fn (Model $model) => new CocktailId((int) $model->rateable_id))->all();
    }

    private static function map(Model $model): Rating
    {
        return Rating::createFromDatabase(
            id: new RatingId((int) $model->id),
            cocktailId: new CocktailId((int) $model->rateable_id),
            memberId: new MemberId((int) $model->user_id),
            value: (int) $model->rating,
            createdAt: new DateTimeImmutable((string) ($model->created_at ?? 'now')),
            updatedAt: new DateTimeImmutable((string) ($model->updated_at ?? 'now')),
        );
    }
}
