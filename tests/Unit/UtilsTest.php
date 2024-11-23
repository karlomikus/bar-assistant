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

        $this->assertSame(176.25, Utils::calculateVolume($ingredients));
        $this->assertSame(17.63, Utils::calculateVolume($ingredients, Units::Cl));
        $this->assertSame(5.88, Utils::calculateVolume($ingredients, Units::Oz));
    }
}
