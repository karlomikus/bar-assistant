<?php

declare(strict_types=1);

namespace Tests\Unit\UnitConverter;

use Tests\TestCase;
use InvalidArgumentException;
use Kami\Cocktail\UnitConverter\Cl;
use Kami\Cocktail\UnitConverter\Ml;
use Kami\Cocktail\UnitConverter\Oz;
use Kami\Cocktail\UnitConverter\Dash;
use Kami\Cocktail\UnitConverter\Units;
use Kami\RecipeUtils\RecipeIngredient;
use Kami\Cocktail\UnitConverter\Converter;

class ConverterTest extends TestCase
{
    public function test_oz_conversion(): void
    {
        $this->assertSame(1.0, Oz::fromString('1')->getValue());
        $this->assertSame(1.5, Oz::fromString('1,5')->getValue());
        $this->assertSame(0.5, Oz::fromString('0.5')->getValue());

        $this->assertSame(30.0, Oz::fromString('1')->toMl()->getValue());
        $this->assertSame(15.0, Oz::fromString('1/2')->toMl()->getValue());
        $this->assertSame(45.0, Oz::fromString('1 1/2')->toMl()->getValue());

        $this->assertSame(15.0, Oz::fromString('0.5')->toMl()->getValue());
        $this->assertSame(45.0, Oz::fromString('1.5')->toMl()->getValue());

        $this->assertSame(45.0, Oz::fromString('1½')->toMl()->getValue());
        $this->assertSame(22.5, Oz::fromString('¾')->toMl()->getValue());

        $this->assertSame(3.0, Oz::fromString('1')->toCl()->getValue());
        $this->assertSame(1.5, Oz::fromString('1/2')->toCl()->getValue());
        $this->assertSame(4.5, Oz::fromString('1 1/2')->toCl()->getValue());
    }

    public function test_ml_conversion(): void
    {
        $this->assertSame(1.0, Ml::fromString('1')->getValue());
        $this->assertSame(15.0, Ml::fromString('15.0')->getValue());
        $this->assertSame(30.0, Ml::fromString('30,0')->getValue());

        $this->assertSame(1.0, Ml::fromString('30')->toOz()->getValue());
        $this->assertSame(1.5, Ml::fromString('45')->toOz()->getValue());
        $this->assertSame(0.75, Ml::fromString('22.5')->toOz()->getValue());

        $this->assertSame(3.0, Ml::fromString('30')->toCl()->getValue());
        $this->assertSame(4.5, Ml::fromString('45')->toCl()->getValue());
        $this->assertSame(2.25, Ml::fromString('22.5')->toCl()->getValue());
    }

    public function test_cl_conversion(): void
    {
        $this->assertSame(1.0, Cl::fromString('1')->getValue());
        $this->assertSame(15.0, Cl::fromString('15.0')->getValue());
        $this->assertSame(30.0, Cl::fromString('30,0')->getValue());

        $this->assertSame(1.0, Cl::fromString('3')->toOz()->getValue());
        $this->assertSame(0.5, Cl::fromString('1.5')->toOz()->getValue());

        $this->assertSame(30.0, Cl::fromString('3')->toMl()->getValue());
        $this->assertSame(15.0, Cl::fromString('1.5')->toMl()->getValue());
    }

    public function test_dash_conversion(): void
    {
        $this->assertSame(1.0, Dash::fromString('1')->getValue());
        // $this->assertSame(1.25, Dash::fromString('4')->toMl()->getValue());
        // $this->assertSame(2.5, Dash::fromString('8')->toMl()->getValue());
    }

    public function test_converter(): void
    {
        $testConvert = Converter::tryConvert(new RecipeIngredient('test', '1/2', 'oz', 'test'), Units::Ml);
        $this->assertSame(15.0, $testConvert->amount);
        $this->assertSame('ml', $testConvert->units);

        // $testConvert = Converter::tryConvert(new RecipeIngredient('test', '4', 'dash', 'test'), Units::Oz);
        // $this->assertSame('4', $testConvert->amount);
        // $this->assertSame('dash', $testConvert->units);

        $testConvert = Converter::tryConvert(new RecipeIngredient('test', '1 1/2', '', 'test'), Units::Ml);
        $this->assertSame('1 1/2', $testConvert->amount);
        $this->assertSame('', $testConvert->units);
    }
}
