<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\ABV;

final class ABVTest extends TestCase
{
    public function test_from_accepts_valid_range_values(): void
    {
        $abv = ABV::from(42.5);

        $this->assertSame(42.5, $abv->toFloat());
    }

    public function test_from_rejects_value_below_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ABV strength must be between 0.0 and 100.0');

        ABV::from(-0.01);
    }

    public function test_from_rejects_value_above_one_hundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ABV strength must be between 0.0 and 100.0');

        ABV::from(100.01);
    }
}
