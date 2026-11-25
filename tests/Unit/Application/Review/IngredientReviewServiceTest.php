<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Review;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Review\Recommendation;
use BarAssistant\Domain\Review\IngredientReviewId;
use BarAssistant\Application\Review\IngredientReviewService;
use Tests\Infrastructure\InMemoryIngredientReviewRepository;
use BarAssistant\Application\Review\DTO\CreateIngredientReviewRequest;
use BarAssistant\Application\Review\DTO\UpdateIngredientReviewRequest;
use BarAssistant\Application\Review\Exception\IngredientReviewAlreadyExistsException;

final class IngredientReviewServiceTest extends TestCase
{
    private InMemoryIngredientReviewRepository $repository;
    private IngredientReviewService $service;

    protected function setUp(): void
    {
        $this->repository = new InMemoryIngredientReviewRepository();
        $this->service = new IngredientReviewService($this->repository);
    }

    public function test_create_review_returns_result(): void
    {
        $result = $this->service->createReview(new CreateIngredientReviewRequest(
            barMembershipId: 1,
            ingredientId: 100,
            content: 'Smoky',
            recommendation: Recommendation::Recommend,
        ));

        $this->assertNotNull($result->id);

        $review = $this->repository->findById(new IngredientReviewId($result->id));
        $this->assertNotNull($review);
        $this->assertSame(Recommendation::Recommend, $review->getRecommendation());
    }

    public function test_create_review_throws_when_duplicate(): void
    {
        $request = new CreateIngredientReviewRequest(
            barMembershipId: 1,
            ingredientId: 100,
            content: 'First',
        );
        $this->service->createReview($request);

        $this->expectException(IngredientReviewAlreadyExistsException::class);

        $this->service->createReview($request);
    }

    public function test_different_members_can_review_same_ingredient(): void
    {
        $first = $this->service->createReview(new CreateIngredientReviewRequest(
            barMembershipId: 1,
            ingredientId: 100,
            content: 'Member 1',
        ));
        $second = $this->service->createReview(new CreateIngredientReviewRequest(
            barMembershipId: 2,
            ingredientId: 100,
            content: 'Member 2',
        ));

        $this->assertNotSame($first->id, $second->id);
        $this->assertCount(2, $this->repository->all());
    }

    public function test_update_review_replaces_content(): void
    {
        $result = $this->service->createReview(new CreateIngredientReviewRequest(
            barMembershipId: 1,
            ingredientId: 100,
            content: 'Original',
            recommendation: Recommendation::Avoid,
        ));

        $review = $this->repository->findById(new IngredientReviewId($result->id));
        $this->assertNotNull($review);

        $this->service->updateReview($review, new UpdateIngredientReviewRequest(
            reviewId: $result->id,
            content: 'Revisited',
            recommendation: null,
        ));

        $this->assertSame('Revisited', $review->getContent()->value);
        $this->assertNull($review->getRecommendation());
    }

    public function test_delete_review_removes_row(): void
    {
        $result = $this->service->createReview(new CreateIngredientReviewRequest(
            barMembershipId: 1,
            ingredientId: 100,
            content: 'To be removed',
        ));

        $review = $this->repository->findById(new IngredientReviewId($result->id));
        $this->assertNotNull($review);

        $this->service->deleteReview($review);

        $this->assertCount(0, $this->repository->all());
    }
}
