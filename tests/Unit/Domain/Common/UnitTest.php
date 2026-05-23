<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Unit;
use Kami\RecipeUtils\UnitConverter\Units;

final class UnitTest extends TestCase
{
    public function test_constructor_trims_and_lowercases_value(): void
    {
        $unit = Unit::from('  ML  ');

        $this->assertSame('ml', $unit->value);
    }

    public function test_constructor_handles_null_value(): void
    {
        $unit = Unit::from(null);

        $this->assertSame('', $unit->value);
    }

    public function test_is_topup_returns_true_for_topup(): void
    {
        $unit = Unit::from('topup');

        $this->assertTrue($unit->isTopup());
    }

    public function test_is_topup_returns_true_for_to_top(): void
    {
        $unit = Unit::from('to top');

        $this->assertTrue($unit->isTopup());
    }

    public function test_is_topup_returns_false_for_regular_unit(): void
    {
        $unit = Unit::from('ml');

        $this->assertFalse($unit->isTopup());
    }

    public function test_is_dash_returns_true_for_dash_variations(): void
    {
        $dashUnit = Unit::from('dash');
        $this->assertTrue($dashUnit->isDash());

        $dashes = Unit::from('dashes');
        $this->assertTrue($dashes->isDash());
    }

    public function test_is_dash_returns_false_for_non_dash_unit(): void
    {
        $unit = Unit::from('ml');

        $this->assertFalse($unit->isDash());
    }

    public function test_is_barspoon_returns_true_for_barspoon_variations(): void
    {
        $barspoon = Unit::from('barspoon');
        $this->assertTrue($barspoon->isBarspoon());

        $bsp = Unit::from('bsp');
        $this->assertTrue($bsp->isBarspoon());

        $spoon = Unit::from('teaspoon');
        $this->assertTrue($spoon->isBarspoon());
    }

    public function test_is_barspoon_returns_false_for_non_barspoon_unit(): void
    {
        $unit = Unit::from('ml');

        $this->assertFalse($unit->isBarspoon());
    }

    public function test_is_convertable_returns_true_for_ml(): void
    {
        $unit = Unit::from('ml');

        $this->assertTrue($unit->isConvertable());
    }

    public function test_is_convertable_returns_true_for_oz(): void
    {
        $unit = Unit::from('oz');

        $this->assertTrue($unit->isConvertable());
    }

    public function test_is_convertable_returns_true_for_cl(): void
    {
        $unit = Unit::from('cl');

        $this->assertTrue($unit->isConvertable());
    }

    public function test_is_convertable_returns_false_for_other_units(): void
    {
        $dash = Unit::from('dash');
        $this->assertFalse($dash->isConvertable());

        $barspoon = Unit::from('barspoon');
        $this->assertFalse($barspoon->isConvertable());
    }

    public function test_get_as_enum_returns_dash_enum_for_dash_unit(): void
    {
        $unit = Unit::from('dash');

        $this->assertSame(Units::Dash, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_barspoon_enum_for_barspoon_unit(): void
    {
        $unit = Unit::from('barspoon');

        $this->assertSame(Units::Barspoon, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_ml_enum_for_ml_unit(): void
    {
        $unit = Unit::from('ml');

        $this->assertSame(Units::Ml, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_oz_enum_for_oz_unit(): void
    {
        $unit = Unit::from('oz');

        $this->assertSame(Units::Oz, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_null_for_invalid_unit(): void
    {
        $unit = Unit::from('invalid-unit');

        $this->assertNull($unit->getAsEnum());
    }

    public function test_to_string_returns_value(): void
    {
        $unit = Unit::from('ml');

        $this->assertSame('ml', (string) $unit);
    }

    public function test_json_serialize_returns_value(): void
    {
        $unit = Unit::from('ml');

        $this->assertSame('ml', $unit->jsonSerialize());
    }

    public function test_json_encode_produces_string(): void
    {
        $unit = Unit::from('ml');

        $this->assertSame('"ml"', json_encode($unit));
    }
}
