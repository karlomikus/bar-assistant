<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Calculator\CalculatorId;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Ingredient\Ingredient;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\MaterializedPath;
use BarAssistant\Domain\Ingredient\PriceCategoryId;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Domain\Support\Unit;
use BarAssistant\Domain\User\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class IngredientTest extends TestCase
{
    public function test_name_cannot_be_empty(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient name cannot be empty');

        new Ingredient(
            barId: new BarId(1),
            name: '',
            createdBy: new UserId(1),
        );
    }

    public function test_name_cannot_be_whitespace_only(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient name cannot be empty');

        new Ingredient(
            barId: new BarId(1),
            name: '   ',
            createdBy: new UserId(1),
        );
    }

    public function test_update_details_rejects_empty_name(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient name cannot be empty');

        $ingredient->updateDetails(name: '');
    }

    public function test_strength_cannot_be_negative(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient strength must be between 0.0 and 100.0');

        new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
            strength: -1.0,
        );
    }

    public function test_strength_cannot_exceed_100(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient strength must be between 0.0 and 100.0');

        new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
            strength: 100.1,
        );
    }

    public function test_strength_accepts_zero(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Water',
            createdBy: new UserId(1),
            strength: 0.0,
        );

        $this->assertEquals(0.0, $ingredient->getStrength());
    }

    public function test_strength_accepts_maximum_value(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Pure Alcohol',
            createdBy: new UserId(1),
            strength: 100.0,
        );

        $this->assertEquals(100.0, $ingredient->getStrength());
    }

    public function test_update_details_rejects_invalid_strength(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
            strength: 40.0,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient strength must be between 0.0 and 100.0');

        $ingredient->updateDetails(name: 'Vodka', strength: 150.0);
    }

    public function test_cannot_change_id_of_persisted_ingredient(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $ingredient->setId(new IngredientId(1));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing ingredient');

        $ingredient->setId(new IngredientId(2));
    }

    public function test_transient_ingredient_can_have_id_set(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $this->assertTrue($ingredient->isTransient());

        $ingredient->setId(new IngredientId(1));

        $this->assertFalse($ingredient->isTransient());
        $this->assertEquals(1, $ingredient->getId()->id);
    }

    public function test_parent_ingredient_must_belong_to_same_bar(): void
    {
        $parent = new Ingredient(
            barId: new BarId(1),
            name: 'Gin',
            createdBy: new UserId(1),
        );
        $parent->setId(new IngredientId(1));

        $child = new Ingredient(
            barId: new BarId(2), // Different bar
            name: 'Plymouth Gin',
            createdBy: new UserId(1),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Parent ingredient must belong to the same bar');

        $child->setAsVariantOf($parent);
    }

    public function test_can_set_parent_from_same_bar(): void
    {
        $parent = new Ingredient(
            barId: new BarId(1),
            name: 'Gin',
            createdBy: new UserId(1),
        );
        $parent->setId(new IngredientId(5));

        $child = new Ingredient(
            barId: new BarId(1), // Same bar
            name: 'Plymouth Gin',
            createdBy: new UserId(1),
        );

        $child->setAsVariantOf($parent);

        $this->assertEquals(5, $child->getParentIngredientId()->id);
        $this->assertEquals('5/', $child->getMaterializedPath()->toString());
    }

    public function test_can_clear_parent_by_passing_null(): void
    {
        $parent = new Ingredient(
            barId: new BarId(1),
            name: 'Gin',
            createdBy: new UserId(1),
        );
        $parent->setId(new IngredientId(5));

        $child = new Ingredient(
            barId: new BarId(1),
            name: 'Plymouth Gin',
            createdBy: new UserId(1),
        );

        $child->setAsVariantOf($parent);
        $this->assertNotNull($child->getParentIngredientId());

        $child->setAsVariantOf(null);
        $this->assertNull($child->getParentIngredientId());
        $this->assertTrue($child->getMaterializedPath()->isRoot());
    }

    public function test_ingredient_part_must_belong_to_same_bar(): void
    {
        $mainIngredient = new Ingredient(
            barId: new BarId(1),
            name: 'Complex Mix',
            createdBy: new UserId(1),
        );

        $part = new Ingredient(
            barId: new BarId(2), // Different bar
            name: 'Part A',
            createdBy: new UserId(1),
        );
        $part->setId(new IngredientId(1));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('All ingredient parts must belong to the same bar');

        $mainIngredient->addIngredientPart($part);
    }

    public function test_ingredient_part_must_have_id(): void
    {
        $mainIngredient = new Ingredient(
            barId: new BarId(1),
            name: 'Complex Mix',
            createdBy: new UserId(1),
        );

        $part = new Ingredient(
            barId: new BarId(1),
            name: 'Part A',
            createdBy: new UserId(1),
        );
        // Part doesn't have an ID set

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient part must have an ID assigned');

        $mainIngredient->addIngredientPart($part);
    }

    public function test_ingredient_cannot_contain_itself_as_part(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Self-Referencing Mix',
            createdBy: new UserId(1),
        );
        $ingredient->setId(new IngredientId(1));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ingredient cannot contain itself as a part');

        $ingredient->addIngredientPart($ingredient);
    }

    public function test_can_add_ingredient_parts_from_same_bar(): void
    {
        $mainIngredient = new Ingredient(
            barId: new BarId(1),
            name: 'Complex Mix',
            createdBy: new UserId(1),
        );

        $part1 = new Ingredient(
            barId: new BarId(1),
            name: 'Part A',
            createdBy: new UserId(1),
        );
        $part1->setId(new IngredientId(2));

        $part2 = new Ingredient(
            barId: new BarId(1),
            name: 'Part B',
            createdBy: new UserId(1),
        );
        $part2->setId(new IngredientId(3));

        $mainIngredient->addIngredientPart($part1);
        $mainIngredient->addIngredientPart($part2);

        $parts = $mainIngredient->getIngredientParts();
        $this->assertCount(2, $parts);
        $this->assertTrue($mainIngredient->isComplexIngredient());
    }

    public function test_adding_duplicate_ingredient_part_is_idempotent(): void
    {
        $mainIngredient = new Ingredient(
            barId: new BarId(1),
            name: 'Complex Mix',
            createdBy: new UserId(1),
        );

        $part = new Ingredient(
            barId: new BarId(1),
            name: 'Part A',
            createdBy: new UserId(1),
        );
        $part->setId(new IngredientId(2));

        $mainIngredient->addIngredientPart($part);
        $mainIngredient->addIngredientPart($part); // Add same part again

        $parts = $mainIngredient->getIngredientParts();
        $this->assertCount(1, $parts); // Should still have only one part
    }

    public function test_can_remove_ingredient_part(): void
    {
        $mainIngredient = new Ingredient(
            barId: new BarId(1),
            name: 'Complex Mix',
            createdBy: new UserId(1),
        );

        $part1 = new Ingredient(
            barId: new BarId(1),
            name: 'Part A',
            createdBy: new UserId(1),
        );
        $part1->setId(new IngredientId(2));

        $part2 = new Ingredient(
            barId: new BarId(1),
            name: 'Part B',
            createdBy: new UserId(1),
        );
        $part2->setId(new IngredientId(3));

        $mainIngredient->addIngredientPart($part1);
        $mainIngredient->addIngredientPart($part2);
        $this->assertCount(2, $mainIngredient->getIngredientParts());

        $mainIngredient->removeIngredientPart(new IngredientId(2));
        $this->assertCount(1, $mainIngredient->getIngredientParts());
    }

    public function test_can_remove_all_ingredient_parts(): void
    {
        $mainIngredient = new Ingredient(
            barId: new BarId(1),
            name: 'Complex Mix',
            createdBy: new UserId(1),
        );

        $part1 = new Ingredient(barId: new BarId(1), name: 'Part A', createdBy: new UserId(1));
        $part1->setId(new IngredientId(2));
        $part2 = new Ingredient(barId: new BarId(1), name: 'Part B', createdBy: new UserId(1));
        $part2->setId(new IngredientId(3));

        $mainIngredient->addIngredientPart($part1);
        $mainIngredient->addIngredientPart($part2);
        $this->assertTrue($mainIngredient->isComplexIngredient());

        $mainIngredient->removeAllIngredientParts();
        $this->assertEmpty($mainIngredient->getIngredientParts());
        $this->assertFalse($mainIngredient->isComplexIngredient());
    }

    public function test_can_add_images(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $ingredient->addImage(new ImageId(1));
        $ingredient->addImage(new ImageId(2));

        $images = $ingredient->getImages();
        $this->assertCount(2, $images);
    }

    public function test_adding_duplicate_image_is_idempotent(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $imageId = new ImageId(1);
        $ingredient->addImage($imageId);
        $ingredient->addImage($imageId); // Add same image again

        $images = $ingredient->getImages();
        $this->assertCount(1, $images);
    }

    public function test_can_remove_image(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $ingredient->addImage(new ImageId(1));
        $ingredient->addImage(new ImageId(2));
        $this->assertCount(2, $ingredient->getImages());

        $ingredient->removeImage(new ImageId(1));
        $this->assertCount(1, $ingredient->getImages());
    }

    public function test_can_remove_all_images(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $ingredient->addImage(new ImageId(1));
        $ingredient->addImage(new ImageId(2));
        $this->assertCount(2, $ingredient->getImages());

        $ingredient->removeAllImages();
        $this->assertEmpty($ingredient->getImages());
    }

    public function test_can_add_prices(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
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
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
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
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(10),
        );

        $authors = $ingredient->getAuthors();
        $this->assertEquals(10, $authors->getCreatedBy()->id);
        $this->assertNull($authors->getUpdatedBy());
        $this->assertFalse($authors->isUpdated());
    }

    public function test_ingredient_tracks_updates(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(10),
        );

        $ingredient->wasUpdatedBy(new UserId(20));

        $authors = $ingredient->getAuthors();
        $this->assertEquals(10, $authors->getCreatedBy()->id);
        $this->assertEquals(20, $authors->getUpdatedBy()->id);
        $this->assertTrue($authors->isUpdated());
    }

    public function test_ingredient_tracks_creation_timestamp(): void
    {
        $now = new DateTimeImmutable();
        
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $timestamps = $ingredient->getRecordTimestamps();
        $this->assertInstanceOf(DateTimeImmutable::class, $timestamps->getCreatedAt());
        $this->assertNull($timestamps->getUpdatedAt());
    }

    public function test_ingredient_can_have_explicit_creation_timestamp(): void
    {
        $explicitTime = new DateTimeImmutable('2024-01-01 12:00:00');
        
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
            createdAt: $explicitTime,
        );

        $timestamps = $ingredient->getRecordTimestamps();
        $this->assertEquals($explicitTime, $timestamps->getCreatedAt());
    }

    public function test_ingredient_tracks_update_timestamp(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $ingredient->wasUpdatedBy(new UserId(2));

        $timestamps = $ingredient->getRecordTimestamps();
        $this->assertNotNull($timestamps->getUpdatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $timestamps->getUpdatedAt());
    }

    public function test_is_ancestor_of_returns_true_for_descendant(): void
    {
        $parent = new Ingredient(
            barId: new BarId(1),
            name: 'Gin',
            createdBy: new UserId(1),
            materializedPath: MaterializedPath::fromString('1/'),
        );

        $child = new Ingredient(
            barId: new BarId(1),
            name: 'London Dry Gin',
            createdBy: new UserId(1),
            materializedPath: MaterializedPath::fromString('1/2/'),
        );

        $this->assertTrue($parent->isAncestorOf($child));
        $this->assertFalse($child->isAncestorOf($parent));
    }

    public function test_is_ancestor_of_returns_false_for_unrelated_ingredient(): void
    {
        $ingredient1 = new Ingredient(
            barId: new BarId(1),
            name: 'Gin',
            createdBy: new UserId(1),
            materializedPath: MaterializedPath::fromString('1/'),
        );

        $ingredient2 = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
            materializedPath: MaterializedPath::fromString('2/'),
        );

        $this->assertFalse($ingredient1->isAncestorOf($ingredient2));
        $this->assertFalse($ingredient2->isAncestorOf($ingredient1));
    }

    public function test_can_create_ingredient_with_all_properties(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Premium Gin',
            createdBy: new UserId(10),
            description: 'A fine London Dry Gin',
            strength: 47.3,
            origin: 'England',
            color: Color::fromHexString('#ffffff'),
            calculatorId: new CalculatorId(5),
            sugarContent: 0.0,
            acidity: 0.0,
            distillery: 'Test Distillery',
            units: new Unit('ml'),
            parentIngredientId: new IngredientId(3),
            materializedPath: MaterializedPath::fromString('3/'),
            createdAt: new DateTimeImmutable('2024-01-01'),
        );

        $this->assertEquals('Premium Gin', $ingredient->getName());
        $this->assertEquals('A fine London Dry Gin', $ingredient->getDescription());
        $this->assertEquals(47.3, $ingredient->getStrength());
        $this->assertEquals('England', $ingredient->getOrigin());
        $this->assertInstanceOf(Color::class, $ingredient->getColor());
        $this->assertEquals(5, $ingredient->getCalculatorId()->id);
        $this->assertEquals(0.0, $ingredient->getSugarContent());
        $this->assertEquals(0.0, $ingredient->getAcidity());
        $this->assertEquals('Test Distillery', $ingredient->getDistillery());
        $this->assertEquals('ml', $ingredient->getUnits()->value);
        $this->assertEquals(3, $ingredient->getParentIngredientId()->id);
        $this->assertEquals('3/', $ingredient->getMaterializedPath()->toString());
    }

    public function test_update_details_modifies_all_mutable_properties(): void
    {
        $ingredient = new Ingredient(
            barId: new BarId(1),
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $ingredient->updateDetails(
            name: 'Premium Vodka',
            description: 'Updated description',
            strength: 50.0,
            origin: 'Russia',
            color: Color::fromHexString('#ffffff'),
            calculatorId: new CalculatorId(3),
            sugarContent: 1.0,
            acidity: 0.5,
            distillery: 'New Distillery',
            units: new Unit('oz'),
        );

        $this->assertEquals('Premium Vodka', $ingredient->getName());
        $this->assertEquals('Updated description', $ingredient->getDescription());
        $this->assertEquals(50.0, $ingredient->getStrength());
        $this->assertEquals('Russia', $ingredient->getOrigin());
        $this->assertEquals(3, $ingredient->getCalculatorId()->id);
        $this->assertEquals(1.0, $ingredient->getSugarContent());
        $this->assertEquals(0.5, $ingredient->getAcidity());
        $this->assertEquals('New Distillery', $ingredient->getDistillery());
        $this->assertEquals('oz', $ingredient->getUnits()->value);
    }

    public function test_bar_id_is_immutable(): void
    {
        $barId = new BarId(1);
        $ingredient = new Ingredient(
            barId: $barId,
            name: 'Vodka',
            createdBy: new UserId(1),
        );

        $this->assertTrue($ingredient->getBarId()->equals($barId));
        
        // BarId should remain the same - there's no setter for it
        // This test documents that barId is a readonly property
    }
}
