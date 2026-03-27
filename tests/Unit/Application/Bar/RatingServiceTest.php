<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Application\Bar\RatingService;
use Tests\Infrastructure\InMemoryRatingRepository;
use BarAssistant\Application\Bar\DTO\RateCocktailRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final class RatingServiceTest extends TestCase
{
    private InMemoryRatingRepository $ratingRepository;
    private RatingService $service;

    protected function setUp(): void
    {
        $this->ratingRepository = new InMemoryRatingRepository();
        $this->service = new RatingService($this->ratingRepository);
    }

    public function test_rate_creates_new_rating(): void
    {
        $request = new RateCocktailRequest(
            memberId: 1,
            cocktailId: 100,
            value: 5,
        );

        $result = $this->service->rate($request);

        $this->assertSame(100, $result->cocktailId);
        $this->assertSame(1, $result->memberId);
        $this->assertSame(5, $result->value);
        $this->assertNotNull($result->id);
    }

    public function test_rate_updates_existing_rating(): void
    {
        // First rating
        $request1 = new RateCocktailRequest(
            memberId: 1,
            cocktailId: 100,
            value: 3,
        );
        $result1 = $this->service->rate($request1);
        $firstId = $result1->id;

        // Update rating
        $request2 = new RateCocktailRequest(
            memberId: 1,
            cocktailId: 100,
            value: 5,
        );
        $result2 = $this->service->rate($request2);

        $this->assertSame($firstId, $result2->id);
        $this->assertSame(5, $result2->value);
    }

    public function test_delete_removes_rating(): void
    {
        // Create a rating
        $request = new RateCocktailRequest(
            memberId: 1,
            cocktailId: 100,
            value: 4,
        );
        $this->service->rate($request);

        // Delete it
        $this->service->delete(1, 100);

        // Verify it's gone
        $ratings = $this->ratingRepository->findByMember(new MemberId(1));
        $this->assertCount(0, $ratings);
    }

    public function test_delete_throws_exception_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Rating not found.');

        $this->service->delete(1, 999);
    }

    public function test_get_for_cocktail_returns_ratings(): void
    {
        $this->service->rate(new RateCocktailRequest(memberId: 1, cocktailId: 100, value: 5));
        $this->service->rate(new RateCocktailRequest(memberId: 2, cocktailId: 100, value: 3));

        $results = $this->service->getForCocktail(100);

        $this->assertCount(2, $results);
    }

    public function test_get_for_member_returns_ratings(): void
    {
        $this->service->rate(new RateCocktailRequest(memberId: 1, cocktailId: 100, value: 5));
        $this->service->rate(new RateCocktailRequest(memberId: 1, cocktailId: 200, value: 4));
        $this->service->rate(new RateCocktailRequest(memberId: 2, cocktailId: 100, value: 3));

        $results = $this->service->getForMember(1);

        $this->assertCount(2, $results);
    }

    public function test_get_low_rated_cocktail_ids_returns_ids_below_threshold(): void
    {
        $this->service->rate(new RateCocktailRequest(memberId: 1, cocktailId: 100, value: 1));
        $this->service->rate(new RateCocktailRequest(memberId: 1, cocktailId: 200, value: 2));
        $this->service->rate(new RateCocktailRequest(memberId: 1, cocktailId: 300, value: 5));
        $this->service->rate(new RateCocktailRequest(memberId: 2, cocktailId: 100, value: 1));

        $ids = $this->service->getLowRatedCocktailIds(1, 2);

        $this->assertContains(100, $ids);
        $this->assertContains(200, $ids);
        $this->assertNotContains(300, $ids);
    }

    public function test_rate_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        $this->service->rate(new RateCocktailRequest(
            memberId: 1,
            cocktailId: 100,
            value: 6,
        ));
    }

    public function test_rate_throws_exception_for_zero_value(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        $this->service->rate(new RateCocktailRequest(
            memberId: 1,
            cocktailId: 100,
            value: 0,
        ));
    }
}
