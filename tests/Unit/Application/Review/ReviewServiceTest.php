<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Review;

use PHPUnit\Framework\TestCase;
use BarAssistant\Application\Review\ReviewService;
use Tests\Infrastructure\InMemoryReviewRepository;
use BarAssistant\Application\Review\DTO\CreateReviewRequest;
use BarAssistant\Application\Review\Exception\ReviewAlreadyExistsException;

final class ReviewServiceTest extends TestCase
{
    private InMemoryReviewRepository $reviewRepository;
    private ReviewService $service;

    protected function setUp(): void
    {
        $this->reviewRepository = new InMemoryReviewRepository();
        $this->service = new ReviewService($this->reviewRepository);
    }

    public function test_create_review_returns_result(): void
    {
        $request = new CreateReviewRequest(
            barMembershipId: 1,
            cocktailId: 100,
            content: 'Great drink',
        );

        $result = $this->service->createReview($request);

        $this->assertNotNull($result->id);
    }

    public function test_create_review_throws_when_duplicate(): void
    {
        $request = new CreateReviewRequest(
            barMembershipId: 1,
            cocktailId: 100,
            content: 'First',
        );
        $this->service->createReview($request);

        $this->expectException(ReviewAlreadyExistsException::class);

        $this->service->createReview($request);
    }

    public function test_different_members_can_review_same_cocktail(): void
    {
        $first = $this->service->createReview(new CreateReviewRequest(
            barMembershipId: 1,
            cocktailId: 100,
            content: 'Member 1 review',
        ));
        $second = $this->service->createReview(new CreateReviewRequest(
            barMembershipId: 2,
            cocktailId: 100,
            content: 'Member 2 review',
        ));

        $this->assertNotSame($first->id, $second->id);
        $this->assertCount(2, $this->reviewRepository->all());
    }

    public function test_delete_review_removes_row(): void
    {
        $result = $this->service->createReview(new CreateReviewRequest(
            barMembershipId: 1,
            cocktailId: 100,
            content: 'To be removed',
        ));

        $review = $this->reviewRepository->findById(new \BarAssistant\Domain\Review\ReviewId($result->id));
        $this->assertNotNull($review);

        $this->service->deleteReview($review);

        $this->assertCount(0, $this->reviewRepository->all());
    }
}
