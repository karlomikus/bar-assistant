<?php

declare(strict_types=1);

namespace Tests\Unit\Scraper;

use Tests\TestCase;
use Kami\Cocktail\Scraper\IngredientParser;

class IngredientParserTest extends TestCase
{
    public function test_parse_ingredient()
    {
        $this->assertSame([
            'amount' => '4',
            'units' => 'oz',
            'name' => 'Ginger beer',
        ], (new IngredientParser('4 ounces (½ cup) ginger beer', false))->parse());

        $this->assertSame([
            'amount' => '0.5',
            'units' => 'oz',
            'name' => 'John D. Taylor\'s Velvet Falernum',
        ], (new IngredientParser('0.5 oz John D. Taylor’s Velvet Falernum', false))->parse());

        $this->assertSame([
            'amount' => '1 1/2',
            'units' => 'oz',
            'name' => 'Mezcal',
        ], (new IngredientParser('1 1/2 oz. mezcal (Talbert uses Del Maguey Vida)', false))->parse());

        $this->assertSame([
            'amount' => '1',
            'units' => 'slice',
            'name' => 'Strawberry',
        ], (new IngredientParser('1 sliced strawberry', false))->parse());

        $this->assertSame([
            'amount' => '2',
            'units' => 'sprigs',
            'name' => 'Mint',
        ], (new IngredientParser('2-3 mint sprig', false))->parse());

        $this->assertSame([
            'amount' => '1/2',
            'units' => 'oz',
            'name' => 'Tequila reposado',
        ], (new IngredientParser('½ ounces* tequila reposado', false))->parse());

        $this->assertSame([
            'amount' => '2',
            'units' => 'oz',
            'name' => 'Spiced rum',
        ], (new IngredientParser('2 oz. spiced rum', false))->parse());

        $this->assertSame([
            'amount' => '0',
            'units' => null,
            'name' => 'Maraschino cherries',
        ], (new IngredientParser('Maraschino cherries', false))->parse());

        $this->assertSame([
            'amount' => '0',
            'units' => 'barspoon',
            'name' => 'Pedro Ximenez',
        ], (new IngredientParser('barspoon Pedro Ximenez', false))->parse());

        $this->assertSame([
            'amount' => '2',
            'units' => 'leaves',
            'name' => 'Large basil',
        ], (new IngredientParser('2-3 large basil leaves', false))->parse());

        $this->assertSame([
            'amount' => '2',
            'units' => 'dash',
            'name' => 'Angostura Bitters',
        ], (new IngredientParser('2 Dashes Angostura Bitters', false))->parse());
    }
}
