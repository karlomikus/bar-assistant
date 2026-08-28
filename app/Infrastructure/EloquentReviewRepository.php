<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Review\Review;
use BarAssistant\Domain\Review\ReviewId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Review\ReviewRepository;
use Kami\Cocktail\Models\CocktailReview as Model;

final class EloquentReviewRepository implements ReviewRepository
{
    public function findById(ReviewId $id): ?Review
    {
        $model = Model::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::mapEntity($model);
    }

    public function save(Review $review): Review
    {
        $model = Model::findOrNew($review->getId()?->value);
        $model->cocktail_id = $review->getCocktailId();
        $model->bar_membership_id = $review->getMemberId()->value;
        $model->content = $review->getContent()->value;
        $model->created_at = $review->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');

        if ($review->getRecordTimestamps()->wasUpdated()) {
            $model->updated_at = $review->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }

        $model->save();

        if ($review->isTransient()) {
            $review->setId(new ReviewId($model->id));
        }

        return $review;
    }

    public function delete(ReviewId $id): void
    {
        Model::destroy($id->value);
    }

    public function existsForMemberAndCocktail(MemberId $memberId, int $cocktailId): bool
    {
        return Model::where('bar_membership_id', $memberId->value)
            ->where('cocktail_id', $cocktailId)
            ->exists();
    }

    private static function mapEntity(Model $model): Review
    {
        $review = Review::create(
            cocktailId: (int) $model->cocktail_id,
            memberId: new MemberId((int) $model->bar_membership_id),
            content: \BarAssistant\Domain\Common\ReviewContent::create($model->content),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
        );

        $review->setId(new ReviewId((int) $model->id));

        return $review;
    }
}
