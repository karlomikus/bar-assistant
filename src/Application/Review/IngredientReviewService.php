<?php

declare(strict_types=1);

namespace BarAssistant\Application\Review;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Common\ReviewContent;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Review\IngredientReview;
use BarAssistant\Domain\Review\IngredientReviewRepository;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Review\DTO\IngredientReviewResult;
use BarAssistant\Application\Review\DTO\CreateIngredientReviewRequest;
use BarAssistant\Application\Review\DTO\UpdateIngredientReviewRequest;
use BarAssistant\Application\Review\Exception\IngredientReviewAlreadyExistsException;

final readonly class IngredientReviewService
{
    public function __construct(
        private IngredientReviewRepository $ingredientReviewRepository,
    ) {
    }

    public function createReview(CreateIngredientReviewRequest $request): IngredientReviewResult
    {
        $memberId = new MemberId($request->barMembershipId);

        if ($this->ingredientReviewRepository->existsForMemberAndIngredient($memberId, $request->ingredientId)) {
            throw new IngredientReviewAlreadyExistsException('A review for this ingredient already exists.');
        }

        $review = IngredientReview::create(
            ingredientId: $request->ingredientId,
            memberId: $memberId,
            content: ReviewContent::create($request->content),
            recommendation: $request->recommendation,
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $review = $this->ingredientReviewRepository->save($review, $request->tasteDescriptors);

        return new IngredientReviewResult(
            id: $review->getId()->value ?? 0,
        );
    }

    public function updateReview(IngredientReview $review, UpdateIngredientReviewRequest $request): void
    {
        if ($review->getId() === null) {
            throw new EntityNotFoundException('Review not found');
        }

        $review->update(
            content: ReviewContent::create($request->content),
            recommendation: $request->recommendation,
        );

        $this->ingredientReviewRepository->save($review, $request->tasteDescriptors);
    }

    public function deleteReview(IngredientReview $review): void
    {
        $id = $review->getId();

        if ($id === null) {
            throw new EntityNotFoundException('Review not found');
        }

        $this->ingredientReviewRepository->delete($id);
    }
}
