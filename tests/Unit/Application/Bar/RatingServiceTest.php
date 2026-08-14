<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Application\Rating\RatingService;
use BarAssistant\Domain\Exception\DomainException;
use Tests\Infrastructure\InMemoryRatingRepository;
use BarAssistant\Application\Rating\DTO\RateCocktailRequest;
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
            barMembershipId: 1,
            cocktailId: 100,
            value: 5,
        );

        $result = $this->service->rate($request);

        $this->assertSame(100, $result->cocktailId);
        $this->assertSame(1, $result->barMembershipId);
        $this->assertSame(5, $result->value);
        $this->assertNotNull($result->id);
    }

    public function test_rate_updates_existing_rating(): void
    {
        $request1 = new RateCocktailRequest(
            barMembershipId: 1,
            cocktailId: 100,
            value: 3,
        );
        $result1 = $this->service->rate($request1);
        $firstId = $result1->id;

        $request2 = new RateCocktailRequest(
            barMembershipId: 1,
            cocktailId: 100,
            value: 5,
        );
        $result2 = $this->service->rate($request2);

        $this->assertSame($firstId, $result2->id);
        $this->assertSame(5, $result2->value);
    }

    public function test_delete_removes_rating(): void
    {
        $request = new RateCocktailRequest(
            barMembershipId: 1,
            cocktailId: 100,
            value: 4,
        );
        $this->service->rate($request);

        $this->service->removeRating(1, 100);

        $this->assertCount(0, $this->ratingRepository->all());
    }

    public function test_delete_throws_exception_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Rating not found.');

        $this->service->removeRating(1, 999);
    }

    public function test_rate_throws_exception_for_invalid_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        $this->service->rate(new RateCocktailRequest(
            barMembershipId: 1,
            cocktailId: 100,
            value: 6,
        ));
    }

    public function test_rate_throws_exception_for_zero_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        $this->service->rate(new RateCocktailRequest(
            barMembershipId: 1,
            cocktailId: 100,
            value: 0,
        ));
    }
}
