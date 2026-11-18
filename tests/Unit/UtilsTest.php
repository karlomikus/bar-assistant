<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Kami\Cocktail\Utils;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Models\ValueObjects\UnitValueObject;
use Kami\Cocktail\Models\ValueObjects\AmountValueObject;

class UtilsTest extends TestCase
{
    public function testCalculateAbv(): void
    {
        $ingredients = [
            ['amount' => 2, 'strength' => 45.0],
            ['amount' => 0, 'strength' => 0],
            ['amount' => 0.04, 'strength' => 44.7],
        ];

        $result = Utils::calculateAbv($ingredients, 20);

        $this->assertSame(37.5, $result);
    }

    public function testCalculateVolume(): void
    {
        $ingredients = [
            new AmountValueObject(30.0, new UnitValueObject('ml')),
            new AmountValueObject(1.5, new UnitValueObject('cl')),
            new AmountValueObject(1, new UnitValueObject('oz')),
            new AmountValueObject(4, new UnitValueObject('dash')),
            new AmountValueObject(500, new UnitValueObject('topup')),
            new AmountValueObject(100, new UnitValueObject('unknown')),
            new AmountValueObject(2, new UnitValueObject('barspoon')),
        ];

        $this->assertSame(146.25, Utils::calculateVolume($ingredients));
        $this->assertSame(14.63, Utils::calculateVolume($ingredients, Units::Cl));
        $this->assertSame(4.88, Utils::calculateVolume($ingredients, Units::Oz));
    }

    public function testGetVolumeInMlConvertsSupportedUnits(): void
    {
        $this->assertSame(30.0, Utils::getVolumeInMl(new AmountValueObject(30.0, new UnitValueObject('ml'))));
        $this->assertSame(10.0, Utils::getVolumeInMl(new AmountValueObject(1.0, new UnitValueObject('cl'))));
        $this->assertSame(30.0, Utils::getVolumeInMl(new AmountValueObject(1.0, new UnitValueObject('oz'))));
        $this->assertSame(0.3125, Utils::getVolumeInMl(new AmountValueObject(1.0, new UnitValueObject('dash'))));
        $this->assertSame(5.0, Utils::getVolumeInMl(new AmountValueObject(1.0, new UnitValueObject('barspoon'))));
    }

    public function testGetVolumeInMlUsesFixedVolumeForTopup(): void
    {
        $this->assertSame(60.0, Utils::getVolumeInMl(new AmountValueObject(1.0, new UnitValueObject('topup'))));
        $this->assertSame(60.0, Utils::getVolumeInMl(new AmountValueObject(0.0, new UnitValueObject('to top'))));
    }

    public function testGetVolumeInMlReturnsNullForUnsupportedUnit(): void
    {
        $this->assertNull(Utils::getVolumeInMl(new AmountValueObject(1.0, new UnitValueObject('unknown'))));
    }
}
