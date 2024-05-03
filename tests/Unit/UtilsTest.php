<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Kami\Cocktail\Utils;
use Kami\RecipeUtils\UnitConverter\Units;

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
            ['amount' => 30.0, 'units' => 'ml'],
            ['amount' => 1.5, 'units' => 'cl'],
            ['amount' => 1, 'units' => 'oz'],
            ['amount' => 4, 'units' => 'dash'],
            ['amount' => 500, 'units' => 'topup'],
            ['amount' => 100, 'units' => 'unknown'],
            ['amount' => 2, 'units' => 'barspoon'],
        ];

        $this->assertSame(176.25, Utils::calculateVolume($ingredients));
        $this->assertSame(17.63, Utils::calculateVolume($ingredients, Units::Cl));
        $this->assertSame(5.88, Utils::calculateVolume($ingredients, Units::Oz));
    }
}
