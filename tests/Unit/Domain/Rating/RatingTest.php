<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rating;

use DomainException;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RatingId;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Rating\RateableType;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\User\UserId;

final class RatingTest extends TestCase
{
    private function makeRating(int $cocktailId = 1, int $userId = 10, int $value = 3): Rating
    {
        return Rating::create(
            rateableId: new RateableId($cocktailId),
            type: RateableType::Cocktail,
            userId: new UserId($userId),
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

    public function test_create_stores_user_id(): void
    {
        $rating = $this->makeRating(userId: 99);

        $this->assertSame(99, $rating->getUserId()->value);
    }

    public function test_create_stores_value(): void
    {
        $rating = $this->makeRating(value: 4);

        $this->assertSame(4, $rating->getValue()->value);
    }

    public function test_create_sets_created_at_and_updated_at(): void
    {
        $before = new \DateTimeImmutable();
        $rating = $this->makeRating();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $rating->getCreatedAt());
        $this->assertLessThanOrEqual($after, $rating->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $rating->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $rating->getUpdatedAt());
    }

    public function test_set_id_assigns_identity(): void
    {
        $rating = $this->makeRating();
        $rating->setId(new RatingId(7));

        $this->assertFalse($rating->isTransient());
        $this->assertSame(7, $rating->getId()->value);
    }

    public function test_set_id_returns_fluent_self(): void
    {
        $rating = $this->makeRating();
        $result = $rating->setId(new RatingId(1));

        $this->assertSame($rating, $result);
    }

    public function test_set_id_throws_when_already_assigned(): void
    {
        $rating = $this->makeRating();
        $rating->setId(new RatingId(1));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing rating');

        $rating->setId(new RatingId(2));
    }

    public function test_update_value_changes_rating_value(): void
    {
        $rating = $this->makeRating(value: 2);
        $rating->updateValue(RatingValue::create(5));

        $this->assertSame(5, $rating->getValue()->value);
    }

    public function test_update_value_advances_updated_at(): void
    {
        $rating = $this->makeRating();
        $originalUpdatedAt = $rating->getUpdatedAt();

        // Ensure clock ticks
        usleep(1000);
        $rating->updateValue(RatingValue::create(1));

        $this->assertGreaterThan($originalUpdatedAt, $rating->getUpdatedAt());
    }

    public function test_update_value_does_not_change_created_at(): void
    {
        $rating = $this->makeRating(value: 3);
        $originalCreatedAt = $rating->getCreatedAt();

        usleep(1000);
        $rating->updateValue(RatingValue::create(5));

        $this->assertEquals($originalCreatedAt, $rating->getCreatedAt());
    }

    public function test_update_value_returns_fluent_self(): void
    {
        $rating = $this->makeRating();
        $result = $rating->updateValue(RatingValue::create(4));

        $this->assertSame($rating, $result);
    }

    public function test_created_at_and_updated_at_are_equal_on_creation(): void
    {
        $rating = $this->makeRating();

        $this->assertEquals($rating->getCreatedAt(), $rating->getUpdatedAt());
    }
}
