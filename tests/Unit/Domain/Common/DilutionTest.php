<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Dilution;

final class DilutionTest extends TestCase
{
    public function test_initialization(): void
    {
        $dilution = Dilution::fromFloat(50.0);
        $this->assertSame(50.0, $dilution->toFloat());
        $this->assertSame(0.5, $dilution->toDecimal());
        $this->assertSame('50', (string) $dilution);
    }

    public function test_initialization_boundaries(): void
    {
        $zero = Dilution::fromFloat(0.0);
        $this->assertSame(0.0, $zero->toFloat());
        $this->assertSame(0.0, $zero->toDecimal());

        $hundred = Dilution::fromFloat(100.0);
        $this->assertSame(100.0, $hundred->toFloat());
        $this->assertSame(1.0, $hundred->toDecimal());
    }

    public function test_throws_exception_for_negative_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dilution must be between 0.0 and 100.0');
        Dilution::fromFloat(-0.1);
    }

    public function test_throws_exception_for_value_over_hundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dilution must be between 0.0 and 100.0');
        Dilution::fromFloat(100.1);
    }
}
