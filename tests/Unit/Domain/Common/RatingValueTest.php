<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Exception\DomainException;

final class RatingValueTest extends TestCase
{
    public function test_create_accepts_minimum_value(): void
    {
        $rating = RatingValue::create(1);

        $this->assertSame(1, $rating->value);
    }

    public function test_create_accepts_maximum_value(): void
    {
        $rating = RatingValue::create(5);

        $this->assertSame(5, $rating->value);
    }

    public function test_create_accepts_midpoint_value(): void
    {
        $rating = RatingValue::create(3);

        $this->assertSame(3, $rating->value);
    }

    public function test_create_rejects_zero(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        RatingValue::create(0);
    }

    public function test_create_rejects_negative_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        RatingValue::create(-1);
    }

    public function test_create_rejects_value_above_maximum(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        RatingValue::create(6);
    }

    public function test_create_rejects_large_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        RatingValue::create(100);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validRatingProvider')]
    public function test_create_accepts_all_valid_values(int $value): void
    {
        $rating = RatingValue::create($value);

        $this->assertSame($value, $rating->value);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function validRatingProvider(): array
    {
        return [
            'one star'   => [1],
            'two stars'  => [2],
            'three stars' => [3],
            'four stars' => [4],
            'five stars' => [5],
        ];
    }
}
