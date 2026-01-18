<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Cocktail;

use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\CocktailIngredient;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\Ingredient\IngredientId;
use PHPUnit\Framework\TestCase;

final class CocktailTest extends TestCase
{
    public function test_getABV_returns_zero_when_dilution_is_null(): void
    {
        $cocktail = new Cocktail(
            name: Name::fromString('Test Cocktail'),
            dilution: null,
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(0.0, $abv->toFloat());
    }

    public function test_getABV_returns_zero_when_total_volume_after_dilution_is_zero(): void
    {
        $cocktail = new Cocktail(
            name: Name::fromString('Test Cocktail'),
            dilution: Dilution::fromFloat(0.0),
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(0.0, $abv->toFloat());
    }

    public function test_getABV_calculates_correctly_with_single_ingredient(): void
    {
        $ingredient = new CocktailIngredient(
            ingredientId: new IngredientId(1),
            amountWithUnits: new AmountWithUnits(60.0, Unit::from('ml')),
            abv: 40.0,
            isOptional: false,
            isSpecific: false,
        );

        $cocktail = new Cocktail(
            name: Name::fromString('Test Cocktail'),
            dilution: Dilution::fromFloat(25.0),
            ingredients: [$ingredient],
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(32.0, $abv->toFloat());
    }

    public function test_getABV_calculates_correctly_with_multiple_ingredients(): void
    {
        $ingredient1 = new CocktailIngredient(
            ingredientId: new IngredientId(1),
            amountWithUnits: new AmountWithUnits(45.0, Unit::from('ml')),
            abv: 40.0,
            isOptional: false,
            isSpecific: false,
        );

        $ingredient2 = new CocktailIngredient(
            ingredientId: new IngredientId(2),
            amountWithUnits: new AmountWithUnits(15.0, Unit::from('ml')),
            abv: 20.0,
            isOptional: false,
            isSpecific: false,
        );

        $ingredient3 = new CocktailIngredient(
            ingredientId: new IngredientId(3),
            amountWithUnits: new AmountWithUnits(30.0, Unit::from('ml')),
            abv: 0.0, // Non-alcoholic ingredient
            isOptional: false,
            isSpecific: false,
        );

        $cocktail = new Cocktail(
            name: Name::fromString('Test Cocktail'),
            dilution: Dilution::fromFloat(20.0),
            ingredients: [$ingredient1, $ingredient2, $ingredient3],
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(19.44, $abv->toFloat());
    }

    public function test_getABV_rounds_to_two_decimal_places(): void
    {
        $ingredient = new CocktailIngredient(
            ingredientId: new IngredientId(1),
            amountWithUnits: new AmountWithUnits(50.0, Unit::from('ml')),
            abv: 37.5,
            isOptional: false,
            isSpecific: false,
        );

        $cocktail = new Cocktail(
            name: Name::fromString('Test Cocktail'),
            dilution: Dilution::fromFloat(33.33),
            ingredients: [$ingredient],
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(28.13, $abv->toFloat());
    }

    public function test_getABV_handles_high_dilution(): void
    {
        $ingredient = new CocktailIngredient(
            ingredientId: new IngredientId(1),
            amountWithUnits: new AmountWithUnits(50.0, Unit::from('ml')),
            abv: 40.0,
            isOptional: false,
            isSpecific: false,
        );

        $cocktail = new Cocktail(
            name: Name::fromString('Test Cocktail'),
            dilution: Dilution::fromFloat(100.0), // 100% dilution doubles the volume
            ingredients: [$ingredient],
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(20.0, $abv->toFloat());
    }

    public function test_getABV_with_no_ingredients(): void
    {
        $cocktail = new Cocktail(
            name: Name::fromString('Test Cocktail'),
            dilution: Dilution::fromFloat(25.0),
            ingredients: [],
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(0.0, $abv->toFloat());
    }
}
