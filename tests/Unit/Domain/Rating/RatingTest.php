<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rating;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Rating\RateableType;

final class RatingTest extends TestCase
{
    private function makeRating(int $cocktailId = 1, int $memberId = 10, int $value = 3): Rating
    {
        return Rating::create(
            rateableId: new RateableId($cocktailId),
            type: RateableType::Cocktail,
            memberId: new MemberId($memberId),
            value: RatingValue::create($value),
        );
    }

    public function test_create_produces_transient_rating(): void
    {
        $rating = $this->makeRating();

        $this->assertTrue($rating->isTransient());
        $this->assertNull($rating->getId());
    }

    public function test_create_stores_rateable_id(): void
    {
        $rating = $this->makeRating(cocktailId: 42);

        $this->assertSame(42, $rating->getRateableId()->value);
    }

    public function test_create_stores_type(): void
    {
        $rating = $this->makeRating();

        $this->assertSame(RateableType::Cocktail, $rating->getType());
    }

    public function test_create_stores_member_id(): void
    {
        $rating = $this->makeRating(memberId: 99);

        $this->assertSame(99, $rating->getMemberId()->value);
    }

    public function test_create_stores_value(): void
    {
        $rating = $this->makeRating(value: 4);

        $this->assertSame(4, $rating->getValue()->value);
    }
}
