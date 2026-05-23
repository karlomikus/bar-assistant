<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ingredient;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Color;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\Ingredient\ComplexIngredientPart;

final class IngredientTest extends TestCase
{
    public function test_cannot_change_id_of_persisted_ingredient(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $ingredient->setId(new IngredientId(1));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing ingredient');

        $ingredient->setId(new IngredientId(2));
    }

    public function test_transient_ingredient_can_have_id_set(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $this->assertTrue($ingredient->isTransient());

        $ingredient->setId(new IngredientId(1));

        $this->assertFalse($ingredient->isTransient());
        $this->assertEquals(1, $ingredient->getId()->value);
    }

    public function test_root_ingredient_rejects_non_root_materialized_path_on_create(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Root ingredient cannot have a materialized path');

        Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            materializedPath: MaterializedPath::fromString('5/'),
        );
    }

    public function test_variant_ingredient_requires_non_root_materialized_path_on_create(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Child ingredient must have a materialized path');

        Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Plymouth Gin'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            parentIngredientId: new IngredientId(5),
        );
    }

    public function test_variant_ingredient_requires_parent_path_match_on_create(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Parent ingredient ID must match materialized path');

        Ingredient::create(
            barId: new BarId(1), // Same bar
            name: Name::fromString('Plymouth Gin'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            parentIngredientId: new IngredientId(6),
            materializedPath: MaterializedPath::fromString('5/'),
        );
    }

    public function test_can_create_variant_with_hierarchy_state(): void
    {
        $child = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Plymouth Gin'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            parentIngredientId: new IngredientId(5),
            materializedPath: MaterializedPath::fromString('5/'),
        );

        $this->assertSame(5, $child->getParentIngredientId()?->value);
        $this->assertSame('5/', $child->getMaterializedPath()->toString());
    }

    public function test_can_add_ingredient_parts(): void
    {
        $mainIngredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Complex Mix'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        )->setId(new IngredientId(1));

        $part1 = ComplexIngredientPart::create(
            ingredientId: new IngredientId(2),
            amountWithUnits: AmountWithUnits::from(200.0, Unit::from('ml')),
        );

        $part2 = ComplexIngredientPart::create(
            ingredientId: new IngredientId(3),
            amountWithUnits: AmountWithUnits::from(100.0, Unit::from('g')),
            note: 'freshly squeezed',
        );

        $mainIngredient->addIngredientPart($part1);
        $mainIngredient->addIngredientPart($part2);

        $parts = $mainIngredient->getIngredientParts();
        $this->assertCount(2, $parts);
        $this->assertTrue($mainIngredient->isComplexIngredient());

        $this->assertSame(200.0, $parts[0]->getAmountWithUnits()->amountMin);
        $this->assertSame('ml', $parts[0]->getAmountWithUnits()->units->value);
        $this->assertSame(100.0, $parts[1]->getAmountWithUnits()->amountMin);
        $this->assertSame('g', $parts[1]->getAmountWithUnits()->units->value);
        $this->assertSame('freshly squeezed', $parts[1]->getNote());
    }

    public function test_adding_duplicate_ingredient_part_is_idempotent(): void
    {
        $mainIngredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Complex Mix'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        )->setId(new IngredientId(1));

        $part = ComplexIngredientPart::create(
            ingredientId: new IngredientId(2),
            amountWithUnits: AmountWithUnits::from(200.0, Unit::from('ml')),
        );

        $mainIngredient->addIngredientPart($part);
        $mainIngredient->addIngredientPart($part); // Add same part again

        $parts = $mainIngredient->getIngredientParts();
        $this->assertCount(1, $parts); // Should still have only one part
    }

    public function test_can_remove_ingredient_part(): void
    {
        $mainIngredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Complex Mix'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        )->setId(new IngredientId(1));

        $part1 = ComplexIngredientPart::create(
            ingredientId: new IngredientId(2),
            amountWithUnits: AmountWithUnits::from(200.0, Unit::from('ml')),
        );

        $part2 = ComplexIngredientPart::create(
            ingredientId: new IngredientId(3),
            amountWithUnits: AmountWithUnits::from(100.0, Unit::from('g')),
        );

        $mainIngredient->addIngredientPart($part1);
        $mainIngredient->addIngredientPart($part2);
        $this->assertCount(2, $mainIngredient->getIngredientParts());

        $mainIngredient->removeIngredientPart(new IngredientId(2));
        $this->assertCount(1, $mainIngredient->getIngredientParts());
    }

    public function test_can_remove_all_ingredient_parts(): void
    {
        $mainIngredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Complex Mix'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        )->setId(new IngredientId(1));

        $part1 = ComplexIngredientPart::create(
            ingredientId: new IngredientId(2),
            amountWithUnits: AmountWithUnits::from(200.0, Unit::from('ml')),
        );

        $part2 = ComplexIngredientPart::create(
            ingredientId: new IngredientId(3),
            amountWithUnits: AmountWithUnits::from(100.0, Unit::from('g')),
        );

        $mainIngredient->addIngredientPart($part1);
        $mainIngredient->addIngredientPart($part2);
        $this->assertTrue($mainIngredient->isComplexIngredient());

        $mainIngredient->removeAllIngredientParts();
        $this->assertEmpty($mainIngredient->getIngredientParts());
        $this->assertFalse($mainIngredient->isComplexIngredient());
    }

    public function test_can_add_images(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $ingredient->addImage(new ImageId(1));
        $ingredient->addImage(new ImageId(2));

        $images = $ingredient->getImages();
        $this->assertCount(2, $images);
    }

    public function test_adding_duplicate_image_is_idempotent(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $imageId = new ImageId(1);
        $ingredient->addImage($imageId);
        $ingredient->addImage($imageId); // Add same image again

        $images = $ingredient->getImages();
        $this->assertCount(1, $images);
    }

    public function test_can_remove_image(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $ingredient->addImage(new ImageId(1));
        $ingredient->addImage(new ImageId(2));
        $this->assertCount(2, $ingredient->getImages());

        $ingredient->removeImage(new ImageId(1));
        $this->assertCount(1, $ingredient->getImages());
    }

    public function test_can_remove_all_images(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $ingredient->addImage(new ImageId(1));
        $ingredient->addImage(new ImageId(2));
        $this->assertCount(2, $ingredient->getImages());

        $ingredient->removeAllImages();
        $this->assertEmpty($ingredient->getImages());
    }

    public function test_can_add_prices(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $ingredient->addPrice(
            priceCategoryId: new PriceCategoryId(1),
            price: 2500,
            currency: 'USD',
            amount: 750.0,
            units: 'ml',
        );

        $prices = $ingredient->getPrices();
        $this->assertCount(1, $prices);
    }

    public function test_can_remove_all_prices(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $ingredient->addPrice(
            priceCategoryId: new PriceCategoryId(1),
            price: 2500,
            currency: 'USD',
            amount: 750.0,
            units: 'ml',
        );
        $ingredient->addPrice(
            priceCategoryId: new PriceCategoryId(2),
            price: 5000,
            currency: 'USD',
            amount: 1000.0,
            units: 'ml',
        );
        $this->assertCount(2, $ingredient->getPrices());

        $ingredient->removeAllPrices();
        $this->assertEmpty($ingredient->getPrices());
    }

    public function test_ingredient_tracks_creator(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(10)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $authors = $ingredient->getAuthors();
        $this->assertEquals(10, $authors->getCreatedBy()->value);
        $this->assertNull($authors->getUpdatedBy());
        $this->assertFalse($authors->isUpdated());
    }

    public function test_ingredient_tracks_updates(): void
    {
        $ingredient = (Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(10)),
            recordTimestamps: RecordTimestamps::createdNow(),
        ))->setId(new IngredientId(24));

        $ingredient->updateDetails(Name::fromString('Vodka'), new UserId(20));

        $authors = $ingredient->getAuthors();
        $this->assertEquals(10, $authors->getCreatedBy()->value);
        $this->assertEquals(20, $authors->getUpdatedBy()->value);
        $this->assertTrue($authors->isUpdated());
    }

    public function test_ingredient_tracks_creation_timestamp(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $timestamps = $ingredient->getRecordTimestamps();
        $this->assertInstanceOf(DateTimeImmutable::class, $timestamps->getCreatedAt());
        $this->assertNull($timestamps->getUpdatedAt());
    }

    public function test_ingredient_can_have_explicit_creation_timestamp(): void
    {
        $explicitTime = new DateTimeImmutable('2024-01-01 12:00:00');

        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdAt($explicitTime),
        );

        $timestamps = $ingredient->getRecordTimestamps();
        $this->assertEquals($explicitTime, $timestamps->getCreatedAt());
    }

    public function test_ingredient_tracks_update_timestamp(): void
    {
        $ingredient = (Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        ))->setId(new IngredientId(24));

        $ingredient->updateDetails(Name::fromString('Vodka'), new UserId(2));

        $timestamps = $ingredient->getRecordTimestamps();
        $this->assertNotNull($timestamps->getUpdatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $timestamps->getUpdatedAt());
    }

    public function test_is_ancestor_of_returns_true_for_descendant(): void
    {
        $parent = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Gin'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            parentIngredientId: new IngredientId(1),
            materializedPath: MaterializedPath::fromString('1/'),
        );

        $child = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('London Dry Gin'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            parentIngredientId: new IngredientId(2),
            materializedPath: MaterializedPath::fromString('1/2/'),
        );

        $this->assertTrue($parent->isAncestorOf($child));
        $this->assertFalse($child->isAncestorOf($parent));
    }

    public function test_is_ancestor_of_returns_false_for_unrelated_ingredient(): void
    {
        $ingredient1 = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Gin'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            parentIngredientId: new IngredientId(1),
            materializedPath: MaterializedPath::fromString('1/'),
        );

        $ingredient2 = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
            parentIngredientId: new IngredientId(2),
            materializedPath: MaterializedPath::fromString('2/'),
        );

        $this->assertFalse($ingredient1->isAncestorOf($ingredient2));
        $this->assertFalse($ingredient2->isAncestorOf($ingredient1));
    }

    public function test_can_create_ingredient_with_all_properties(): void
    {
        $ingredient = Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Premium Gin'),
            authors: Authors::createdBy(new UserId(10)),
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2024-01-01')),
            description: 'A fine London Dry Gin',
            strength: ABV::from(47.3),
            origin: 'England',
            color: Color::fromHexString('#ffffff'),
            calculatorId: new CalculatorId(5),
            sugarContent: 0.0,
            acidity: 0.0,
            distillery: 'Test Distillery',
            units: Unit::from('ml'),
            parentIngredientId: new IngredientId(3),
            materializedPath: MaterializedPath::fromString('3/'),
        );

        $this->assertEquals('Premium Gin', $ingredient->getName());
        $this->assertEquals('A fine London Dry Gin', $ingredient->getDescription());
        $this->assertEquals(47.3, $ingredient->getStrength()->toFloat());
        $this->assertEquals('England', $ingredient->getOrigin());
        $this->assertInstanceOf(Color::class, $ingredient->getColor());
        $this->assertEquals(5, $ingredient->getCalculatorId()->value);
        $this->assertEquals(0.0, $ingredient->getSugarContent());
        $this->assertEquals(0.0, $ingredient->getAcidity());
        $this->assertEquals('Test Distillery', $ingredient->getDistillery());
        $this->assertEquals('ml', $ingredient->getUnits()->value);
        $this->assertEquals(3, $ingredient->getParentIngredientId()->value);
        $this->assertEquals('3/', $ingredient->getMaterializedPath()->toString());
    }

    public function test_update_details_modifies_all_mutable_properties(): void
    {
        $ingredient = (Ingredient::create(
            barId: new BarId(1),
            name: Name::fromString('Vodka'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: RecordTimestamps::createdNow(),
        ))->setId(new IngredientId(24));

        $ingredient->updateDetails(
            name: Name::fromString('Premium Vodka'),
            description: 'Updated description',
            updatedBy: new UserId(5),
            strength: ABV::from(50.0),
            origin: 'Russia',
            color: Color::fromHexString('#ffffff'),
            calculatorId: new CalculatorId(3),
            sugarContent: 1.0,
            acidity: 0.5,
            distillery: 'New Distillery',
            units: Unit::from('oz'),
        );

        $this->assertEquals('Premium Vodka', $ingredient->getName());
        $this->assertEquals('Updated description', $ingredient->getDescription());
        $this->assertEquals(50.0, $ingredient->getStrength()->toFloat());
        $this->assertEquals('Russia', $ingredient->getOrigin());
        $this->assertEquals(3, $ingredient->getCalculatorId()->value);
        $this->assertEquals(1.0, $ingredient->getSugarContent());
        $this->assertEquals(0.5, $ingredient->getAcidity());
        $this->assertEquals('New Distillery', $ingredient->getDistillery());
        $this->assertEquals('oz', $ingredient->getUnits()->value);
    }
}
