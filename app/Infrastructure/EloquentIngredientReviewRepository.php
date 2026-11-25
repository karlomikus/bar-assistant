<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Bar\MemberId;
use Kami\Cocktail\Models\TasteDescriptor;
use BarAssistant\Domain\Common\ReviewContent;
use BarAssistant\Domain\Review\Recommendation;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Review\IngredientReview;
use BarAssistant\Domain\Review\IngredientReviewId;
use Kami\Cocktail\Models\IngredientReview as Model;
use BarAssistant\Domain\Review\IngredientReviewRepository;

final class EloquentIngredientReviewRepository implements IngredientReviewRepository
{
    public function findById(IngredientReviewId $id): ?IngredientReview
    {
        $model = Model::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::mapEntity($model);
    }

    public function findForMemberAndIngredient(MemberId $memberId, int $ingredientId): ?IngredientReview
    {
        $model = Model::where('bar_membership_id', $memberId->value)
            ->where('ingredient_id', $ingredientId)
            ->first();

        if ($model === null) {
            return null;
        }

        return self::mapEntity($model);
    }

    public function existsForMemberAndIngredient(MemberId $memberId, int $ingredientId): bool
    {
        return Model::where('bar_membership_id', $memberId->value)
            ->where('ingredient_id', $ingredientId)
            ->exists();
    }

    public function save(IngredientReview $review, array $tasteDescriptorNames = []): IngredientReview
    {
        return DB::transaction(function () use ($review, $tasteDescriptorNames): IngredientReview {
            $model = Model::findOrNew($review->getId()?->value);
            $model->ingredient_id = $review->getIngredientId();
            $model->bar_membership_id = $review->getMemberId()->value;
            $model->content = $review->getContent()->value;
            $model->recommendation = $review->getRecommendation()?->value;
            $model->created_at = $review->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');

            if ($review->getRecordTimestamps()->wasUpdated()) {
                $model->updated_at = $review->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
            }

            $model->save();

            $descriptorIds = $this->resolveTasteDescriptorIds($tasteDescriptorNames);
            $model->tasteDescriptors()->sync($descriptorIds);

            if ($review->isTransient()) {
                $review->setId(new IngredientReviewId($model->id));
            }

            return $review;
        });
    }

    public function delete(IngredientReviewId $id): void
    {
        Model::destroy($id->value);
    }

    /**
     * @param string[] $names
     * @return int[]
     */
    private function resolveTasteDescriptorIds(array $names): array
    {
        $normalizedNames = array_values(array_unique(array_filter(
            array_map(static fn (string $name): string => mb_strtolower(trim($name)), $names),
        )));

        if ($normalizedNames === []) {
            return [];
        }

        /** @var int[] $descriptorIds */
        $descriptorIds = TasteDescriptor::whereIn('normalized_name', $normalizedNames)
            ->pluck('id')
            ->all();

        return $descriptorIds;
    }

    private static function mapEntity(Model $model): IngredientReview
    {
        $review = IngredientReview::create(
            ingredientId: (int) $model->ingredient_id,
            memberId: new MemberId((int) $model->bar_membership_id),
            content: ReviewContent::create($model->content),
            recommendation: $model->recommendation !== null ? Recommendation::from($model->recommendation) : null,
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
        );

        $review->setId(new IngredientReviewId((int) $model->id));

        return $review;
    }
}
