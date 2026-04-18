<?php

declare(strict_types=1);

namespace Tests\Unit\External;

use Tests\TestCase;
use Kami\Cocktail\External\Model\CocktailIngredient;

class CocktailIngredientTest extends TestCase
{
    public function testFromSchema4ArrayBasic(): void
    {
        $source = [
            '_id' => 'gin',
            'name' => 'Gin',
            'amount' => 60.0,
            'units' => 'ml',
            'amount_max' => null,
            'optional' => false,
            'is_specified' => true,
            'note' => 'London dry',
            'sort' => 1,
            'substitutes' => [],
        ];

        $result = CocktailIngredient::fromSchema4Array($source);

        $this->assertSame('Gin', $result->ingredient->name);
        $this->assertSame('gin', $result->ingredient->id);
        $this->assertSame(60.0, $result->amount->amountMin);
        $this->assertSame('ml', $result->amount->units->value);
        $this->assertNull($result->amount->amountMax);
        $this->assertFalse($result->optional);
        $this->assertTrue($result->isSpecified);
        $this->assertSame('London dry', $result->note);
        $this->assertSame(1, $result->sort);
        $this->assertEmpty($result->substitutes);
    }

    public function testFromSchema4ArrayWithSubstituteAsArray(): void
    {
        $source = [
            'name' => 'Rum',
            'amount' => 45.0,
            'units' => 'ml',
            'substitutes' => [
                [
                    '_id' => 'white-rum',
                    'name' => 'White Rum',
                    'amount' => 45.0,
                    'units' => 'ml',
                ],
            ],
        ];

        $result = CocktailIngredient::fromSchema4Array($source);

        $this->assertCount(1, $result->substitutes);
        $this->assertSame('White Rum', $result->substitutes[0]->ingredient->name);
    }

    public function testFromSchema4ArrayGeneratesIdFromNameWhenMissing(): void
    {
        $source = [
            'name' => 'Sweet Vermouth',
            'amount' => 30.0,
            'units' => 'ml',
        ];

        $result = CocktailIngredient::fromSchema4Array($source);

        $this->assertSame('sweet-vermouth', $result->ingredient->id);
    }
}
