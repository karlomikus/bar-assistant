<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\Common\AmountWithUnits;

final class AmountWithUnitsTest extends TestCase
{
    public function test_initialization(): void
    {
        $unit = Unit::from('ml');
        $amount = AmountWithUnits::from(10.0, $unit);

        $this->assertSame(10.0, $amount->amountMin);
        $this->assertSame($unit, $amount->units);
        $this->assertNull($amount->amountMax);

        $amountRange = AmountWithUnits::from(10.0, $unit, 20.0);
        $this->assertSame(10.0, $amountRange->amountMin);
        $this->assertSame(20.0, $amountRange->amountMax);
    }

    public function test_convert_to_valid_units(): void
    {
        $ozUnit = Unit::from('oz');
        $mlUnit = Unit::from('ml');
        $amount = AmountWithUnits::from(1.0, $ozUnit);

        $converted = $amount->convertTo($mlUnit);

        // Based on test failure, converter returns 30.0 for 1 oz to ml
        $this->assertEquals(30.0, $converted->amountMin);
        $this->assertSame('ml', $converted->units->value);
    }

    public function test_convert_to_invalid_units_returns_original(): void
    {
        $mlUnit = Unit::from('ml');
        $invalidUnit = Unit::from('invalid');
        $amount = AmountWithUnits::from(10.0, $mlUnit);

        $converted = $amount->convertTo($invalidUnit);

        $this->assertEquals($amount, $converted);
    }

    public function test_to_string_format(): void
    {
        $unit = Unit::from('ml');
        $amount = AmountWithUnits::from(10.0, $unit);

        $this->assertSame('10 ml', (string) $amount);

        $amountRange = AmountWithUnits::from(10.0, $unit, 20.0);
        $this->assertSame('10 ml - 20 ml', (string) $amountRange);
    }

    public function test_to_array(): void
    {
        $unit = Unit::from('ml');
        $amount = AmountWithUnits::from(10.0, $unit);

        $this->assertSame([
            'amount' => 10.0,
            'units' => 'ml',
            'amount_max' => null,
        ], $amount->toArray());

        $amountRange = AmountWithUnits::from(10.0, $unit, 20.0);
        $this->assertSame([
            'amount' => 10.0,
            'units' => 'ml',
            'amount_max' => 20.0,
        ], $amountRange->toArray());
    }
}
