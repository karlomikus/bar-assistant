<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Support;

use BarAssistant\Domain\Common\Unit;
use Kami\RecipeUtils\UnitConverter\Units;
use PHPUnit\Framework\TestCase;

final class UnitTest extends TestCase
{
    public function test_constructor_trims_and_lowercases_value(): void
    {
        $unit = new Unit('  ML  ');

        $this->assertSame('ml', $unit->value);
    }

    public function test_constructor_handles_null_value(): void
    {
        $unit = new Unit(null);

        $this->assertSame('', $unit->value);
    }

    public function test_is_topup_returns_true_for_topup(): void
    {
        $unit = new Unit('topup');

        $this->assertTrue($unit->isTopup());
    }

    public function test_is_topup_returns_true_for_to_top(): void
    {
        $unit = new Unit('to top');

        $this->assertTrue($unit->isTopup());
    }

    public function test_is_topup_returns_false_for_regular_unit(): void
    {
        $unit = new Unit('ml');

        $this->assertFalse($unit->isTopup());
    }

    public function test_is_dash_returns_true_for_dash_variations(): void
    {
        $dashUnit = new Unit('dash');
        $this->assertTrue($dashUnit->isDash());

        $dashes = new Unit('dashes');
        $this->assertTrue($dashes->isDash());
    }

    public function test_is_dash_returns_false_for_non_dash_unit(): void
    {
        $unit = new Unit('ml');

        $this->assertFalse($unit->isDash());
    }

    public function test_is_barspoon_returns_true_for_barspoon_variations(): void
    {
        $barspoon = new Unit('barspoon');
        $this->assertTrue($barspoon->isBarspoon());

        $bsp = new Unit('bsp');
        $this->assertTrue($bsp->isBarspoon());

        $spoon = new Unit('teaspoon');
        $this->assertTrue($spoon->isBarspoon());
    }

    public function test_is_barspoon_returns_false_for_non_barspoon_unit(): void
    {
        $unit = new Unit('ml');

        $this->assertFalse($unit->isBarspoon());
    }

    public function test_is_convertable_returns_true_for_ml(): void
    {
        $unit = new Unit('ml');

        $this->assertTrue($unit->isConvertable());
    }

    public function test_is_convertable_returns_true_for_oz(): void
    {
        $unit = new Unit('oz');

        $this->assertTrue($unit->isConvertable());
    }

    public function test_is_convertable_returns_true_for_cl(): void
    {
        $unit = new Unit('cl');

        $this->assertTrue($unit->isConvertable());
    }

    public function test_is_convertable_returns_false_for_other_units(): void
    {
        $dash = new Unit('dash');
        $this->assertFalse($dash->isConvertable());

        $barspoon = new Unit('barspoon');
        $this->assertFalse($barspoon->isConvertable());
    }

    public function test_get_as_enum_returns_dash_enum_for_dash_unit(): void
    {
        $unit = new Unit('dash');

        $this->assertSame(Units::Dash, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_barspoon_enum_for_barspoon_unit(): void
    {
        $unit = new Unit('barspoon');

        $this->assertSame(Units::Barspoon, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_ml_enum_for_ml_unit(): void
    {
        $unit = new Unit('ml');

        $this->assertSame(Units::Ml, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_oz_enum_for_oz_unit(): void
    {
        $unit = new Unit('oz');

        $this->assertSame(Units::Oz, $unit->getAsEnum());
    }

    public function test_get_as_enum_returns_null_for_invalid_unit(): void
    {
        $unit = new Unit('invalid-unit');

        $this->assertNull($unit->getAsEnum());
    }

    public function test_to_string_returns_value(): void
    {
        $unit = new Unit('ml');

        $this->assertSame('ml', (string) $unit);
    }

    public function test_json_serialize_returns_value(): void
    {
        $unit = new Unit('ml');

        $this->assertSame('ml', $unit->jsonSerialize());
    }

    public function test_json_encode_produces_string(): void
    {
        $unit = new Unit('ml');

        $this->assertSame('"ml"', json_encode($unit));
    }
}
