<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Kami\Cocktail\Utils;

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
}
