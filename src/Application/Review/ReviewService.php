<?php

declare(strict_types=1);

namespace BarAssistant\Application\Review;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Review\Review;
use BarAssistant\Domain\Common\ReviewContent;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Review\ReviewRepository;
use BarAssistant\Application\Review\DTO\ReviewResult;
use BarAssistant\Application\Review\DTO\CreateReviewRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Review\Exception\ReviewAlreadyExistsException;

final readonly class ReviewService
{
    public function __construct(
        private ReviewRepository $reviewRepository,
    ) {
    }

    public function createReview(CreateReviewRequest $request): ReviewResult
    {
        $memberId = new MemberId($request->barMembershipId);

        if ($this->reviewRepository->existsForMemberAndCocktail($memberId, $request->cocktailId)) {
            throw new ReviewAlreadyExistsException('A review for this cocktail already exists.');
        }

        $review = Review::create(
            cocktailId: $request->cocktailId,
            memberId: $memberId,
            content: ReviewContent::create($request->content),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $review = $this->reviewRepository->save($review);

        return new ReviewResult(
            id: $review->getId()->value ?? 0,
        );
    }

    public function deleteReview(Review $review): void
    {
        $id = $review->getId();

        if ($id === null) {
            throw new EntityNotFoundException('Review not found');
        }

        $this->reviewRepository->delete($id);
    }
}
