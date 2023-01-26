<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Kami\Cocktail\Services\Calculator;

class CalculatorTest extends TestCase
{
    public function testCalculateAbv()
    {
        $ingredients = [
            ['amount' => 2, 'strength' => 45.0],
            ['amount' => 0, 'strength' => 0],
            ['amount' => 0.04, 'strength' => 44.7],
        ];

        $result = Calculator::calculateAbv($ingredients, 20);

        $this->assertSame(37.5, $result);
    }
}
