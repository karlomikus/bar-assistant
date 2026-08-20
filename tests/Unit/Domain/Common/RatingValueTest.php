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

        $this->assertSame(1.0, $rating->value);
    }

    public function test_create_accepts_maximum_value(): void
    {
        $rating = RatingValue::create(5);

        $this->assertSame(5.0, $rating->value);
    }

    public function test_create_accepts_midpoint_value(): void
    {
        $rating = RatingValue::create(3);

        $this->assertSame(3.0, $rating->value);
    }

    public function test_create_accepts_half_value(): void
    {
        $rating = RatingValue::create(3.5);

        $this->assertSame(3.5, $rating->value);
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

        RatingValue::create(5.5);
    }

    public function test_create_rejects_large_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be between 1 and 5');

        RatingValue::create(100);
    }

    public function test_create_rejects_sub_half_value(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Rating value must be on a 0.5 step');

        RatingValue::create(3.7);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validRatingProvider')]
    public function test_create_accepts_all_valid_values(float $value): void
    {
        $rating = RatingValue::create($value);

        $this->assertSame($value, $rating->value);
    }

    /**
     * @return array<string, array{float}>
     */
    public static function validRatingProvider(): array
    {
        return [
            'one star'        => [1.0],
            'one and a half'  => [1.5],
            'two stars'       => [2.0],
            'two and a half'   => [2.5],
            'three stars'     => [3.0],
            'three and a half' => [3.5],
            'four stars'      => [4.0],
            'four and a half'  => [4.5],
            'five stars'      => [5.0],
        ];
    }
}
