<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bar;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Rating\RateableType;
use BarAssistant\Application\Rating\RatingService;
use BarAssistant\Domain\Exception\DomainException;
use Tests\Infrastructure\InMemoryRatingRepository;
use BarAssistant\Application\Rating\DTO\RateRequest;
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
        $request = new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 5,
        );

        $result = $this->service->rate($request);

        $this->assertNotNull($result);
        $this->assertSame(100, $result->rateableId);
        $this->assertSame(1, $result->barMembershipId);
        $this->assertSame(5.0, $result->value);
        $this->assertNotNull($result->id);
    }

    public function test_rate_creates_new_half_value_rating(): void
    {
        $request = new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 3.5,
        );

        $result = $this->service->rate($request);

        $this->assertNotNull($result);
        $this->assertSame(3.5, $result->value);
    }

    public function test_rate_supports_ingredient_rateable_type(): void
    {
        $request = new RateRequest(
            barMembershipId: 1,
            rateableId: 200,
            type: RateableType::Ingredient,
            value: 4.5,
        );

        $result = $this->service->rate($request);

        $this->assertNotNull($result);
        $this->assertSame(200, $result->rateableId);
        $this->assertSame(4.5, $result->value);
        $this->assertSame(RateableType::Ingredient, $this->ratingRepository->all()[0]->getType());
    }

    public function test_rate_keeps_cocktail_and_ingredient_ratings_isolated(): void
    {
        $this->service->rate(new RateRequest(barMembershipId: 1, rateableId: 100, type: RateableType::Cocktail, value: 2));
        $this->service->rate(new RateRequest(barMembershipId: 1, rateableId: 100, type: RateableType::Ingredient, value: 4));

        $this->assertCount(2, $this->ratingRepository->all());
        $this->assertSame(2.0, $this->ratingRepository->findMemberRating(new \BarAssistant\Domain\Rating\RateableId(100), RateableType::Cocktail, new \BarAssistant\Domain\Bar\MemberId(1))?->getValue()->value);
        $this->assertSame(4.0, $this->ratingRepository->findMemberRating(new \BarAssistant\Domain\Rating\RateableId(100), RateableType::Ingredient, new \BarAssistant\Domain\Bar\MemberId(1))?->getValue()->value);
    }

    public function test_rate_updates_existing_rating(): void
    {
        $request1 = new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 3,
        );
        $result1 = $this->service->rate($request1);
        $firstId = $result1?->id;

        $request2 = new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 5,
        );
        $result2 = $this->service->rate($request2);

        $this->assertNotNull($result2);
        $this->assertSame($firstId, $result2->id);
        $this->assertSame(5.0, $result2->value);
    }

    public function test_rate_toggles_off_when_same_half_value_submitted(): void
    {
        $request = new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 3.5,
        );
        $this->service->rate($request);

        $toggleResult = $this->service->rate($request);

        $this->assertNull($toggleResult);
        $this->assertCount(0, $this->ratingRepository->all());
    }

    public function test_delete_removes_rating(): void
    {
        $request = new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 4,
        );
        $this->service->rate($request);

        $this->service->removeRating(1, 100, RateableType::Cocktail);

        $this->assertCount(0, $this->ratingRepository->all());
    }

    public function test_delete_throws_exception_when_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Rating not found.');

        $this->service->removeRating(1, 999, RateableType::Cocktail);
    }

    public function test_rate_throws_exception_for_invalid_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        $this->service->rate(new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 6,
        ));
    }

    public function test_rate_throws_exception_for_zero_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        $this->service->rate(new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 0,
        ));
    }

    public function test_rate_throws_exception_for_off_grid_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be on a 0.5 step');

        $this->service->rate(new RateRequest(
            barMembershipId: 1,
            rateableId: 100,
            type: RateableType::Cocktail,
            value: 3.7,
        ));
    }
}
