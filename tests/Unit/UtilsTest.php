<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Kami\Cocktail\Utils;

class UtilsTest extends TestCase
{
    public function test_parse_ingredient()
    {
        $this->assertSame([
            'amount' => 45,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('1½oz'));

        $this->assertSame([
            'amount' => 7.5,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('7,5 ml'));

        $this->assertSame([
            'amount' => 0,
            'units' => 'ml',
        ], Utils::parseIngredientAmount(''));

        $this->assertSame([
            'amount' => 7.5,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('7.5 ml'));

        $this->assertSame([
            'amount' => 30,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('30 ml'));

        $this->assertSame([
            'amount' => 30,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('1 oz'));

        $this->assertSame([
            'amount' => 22.5,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('¾oz'));

        $this->assertSame([
            'amount' => 15,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('½ oz'));

        $this->assertSame([
            'amount' => 2,
            'units' => 'dashes',
        ], Utils::parseIngredientAmount('2 dashes'));

        $this->assertSame([
            'amount' => 1.4,
            'units' => 'parts',
        ], Utils::parseIngredientAmount('1.4 parts'));

        $this->assertSame([
            'amount' => 45,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('4.5 cl'));

        $this->assertSame([
            'amount' => 30,
            'units' => 'ml',
        ], Utils::parseIngredientAmount('3 cl'));
    }
}
