<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Cocktail;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\PublicStatus;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Cocktail\CocktailIngredient;

final class CocktailTest extends TestCase
{
    public function test_getABV_returns_zero_when_dilution_is_null(): void
    {
        $cocktail = Cocktail::create(
            barId: new BarId(1),
            name: Name::fromString('Test Cocktail'),
            instructions: '',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(0.0, $abv->toFloat());
    }

    public function test_getABV_returns_zero_when_total_volume_after_dilution_is_zero(): void
    {
        $cocktail = Cocktail::create(
            barId: new BarId(1),
            name: Name::fromString('Test Cocktail'),
            instructions: '',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            dilution: Dilution::fromFloat(0.0),
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(0.0, $abv->toFloat());
    }

    public function test_getABV_calculates_correctly_with_single_ingredient(): void
    {
        $ingredient = CocktailIngredient::create(
            ingredientId: new IngredientId(1),
            amountWithUnits: AmountWithUnits::from(60.0, Unit::from('ml')),
            abv: ABV::from(40.0),
        );

        $cocktail = Cocktail::create(
            barId: new BarId(1),
            name: Name::fromString('Test Cocktail'),
            instructions: '',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            dilution: Dilution::fromFloat(25.0),
        );

        $cocktail->addIngredient($ingredient);

        $abv = $cocktail->getABV();

        $this->assertEquals(32.0, $abv->toFloat());
    }

    public function test_getABV_calculates_correctly_with_multiple_ingredients(): void
    {
        $ingredient1 = CocktailIngredient::create(
            ingredientId: new IngredientId(1),
            amountWithUnits: AmountWithUnits::from(45.0, Unit::from('ml')),
            abv: ABV::from(40.0),
        );

        $ingredient2 = CocktailIngredient::create(
            ingredientId: new IngredientId(2),
            amountWithUnits: AmountWithUnits::from(15.0, Unit::from('ml')),
            abv: ABV::from(20.0),
        );

        $ingredient3 = CocktailIngredient::create(
            ingredientId: new IngredientId(3),
            amountWithUnits: AmountWithUnits::from(30.0, Unit::from('ml')),
            abv: ABV::from(0.0),
        );

        $cocktail = Cocktail::create(
            barId: new BarId(1),
            name: Name::fromString('Test Cocktail'),
            instructions: '',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            dilution: Dilution::fromFloat(20.0),
        );

        $cocktail->addIngredient($ingredient1);
        $cocktail->addIngredient($ingredient2);
        $cocktail->addIngredient($ingredient3);

        $abv = $cocktail->getABV();

        $this->assertEquals(19.44, $abv->toFloat());
    }

    public function test_getABV_rounds_to_two_decimal_places(): void
    {
        $ingredient = CocktailIngredient::create(
            ingredientId: new IngredientId(1),
            amountWithUnits: AmountWithUnits::from(50.0, Unit::from('ml')),
            abv: ABV::from(37.5),
        );

        $cocktail = Cocktail::create(
            barId: new BarId(1),
            name: Name::fromString('Test Cocktail'),
            instructions: '',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            dilution: Dilution::fromFloat(33.33),
        );

        $cocktail->addIngredient($ingredient);

        $abv = $cocktail->getABV();

        $this->assertEquals(28.13, $abv->toFloat());
    }

    public function test_getABV_handles_high_dilution(): void
    {
        $ingredient = CocktailIngredient::create(
            ingredientId: new IngredientId(1),
            amountWithUnits: AmountWithUnits::from(50.0, Unit::from('ml')),
            abv: ABV::from(40.0),
        );

        $cocktail = Cocktail::create(
            barId: new BarId(1),
            name: Name::fromString('Test Cocktail'),
            instructions: '',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            dilution: Dilution::fromFloat(100.0),
        );

        $cocktail->addIngredient($ingredient);

        $abv = $cocktail->getABV();

        $this->assertEquals(20.0, $abv->toFloat());
    }

    public function test_getABV_with_no_ingredients(): void
    {
        $cocktail = Cocktail::create(
            barId: new BarId(1),
            name: Name::fromString('Test Cocktail'),
            instructions: '',
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            publicStatus: PublicStatus::createPrivate(),
            dilution: Dilution::fromFloat(25.0),
        );

        $abv = $cocktail->getABV();

        $this->assertEquals(0.0, $abv->toFloat());
    }
}
