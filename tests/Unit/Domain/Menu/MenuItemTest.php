<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Menu;

use DomainException;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Menu\MenuItem;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;

final class MenuItemTest extends TestCase
{
    public function test_can_create_menu_item_for_cocktail(): void
    {
        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 0,
        );

        $this->assertTrue($item->isCocktail());
        $this->assertFalse($item->isIngredient());
        $this->assertFalse($item->isBarInventoryAware());
        $this->assertInstanceOf(CocktailId::class, $item->getCocktailId());
        $this->assertNull($item->getIngredientId());
        $this->assertEquals(0, $item->getSortIndex());
    }

    public function test_can_create_menu_item_for_ingredient(): void
    {
        $item = MenuItem::forIngredient(
            ingredientId: new IngredientId(1),
            price: Price::createFromMinor(500, 'USD'),
            sortIndex: 1,
            barInventoryAware: true,
        );

        $this->assertTrue($item->isIngredient());
        $this->assertFalse($item->isCocktail());
        $this->assertTrue($item->isBarInventoryAware());
        $this->assertInstanceOf(IngredientId::class, $item->getIngredientId());
        $this->assertNull($item->getCocktailId());
        $this->assertEquals(1, $item->getSortIndex());
    }

    public function test_cannot_create_menu_item_with_negative_price(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Menu item price must be non-negative');

        MenuItem::forIngredient(
            ingredientId: new IngredientId(1),
            price: Price::createFromMinor(-100, 'USD'),
            sortIndex: 0,
        );
    }

    public function test_cannot_create_menu_item_with_negative_sort_index(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Sort index must be non-negative');

        MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: -1,
        );
    }

    public function test_can_update_price(): void
    {
        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 0,
        );

        $newPrice = Price::createFromMinor(1500, 'USD');
        $updatedItem = $item->withPrice($newPrice);

        $this->assertEquals(1500, $updatedItem->getPrice()->getAsMinor());
        $this->assertEquals(1000, $item->getPrice()->getAsMinor()); // Original unchanged
    }

    public function test_can_update_sort_index(): void
    {
        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 0,
            barInventoryAware: false,
        );

        $updatedItem = $item->withSortIndex(5);

        $this->assertEquals(5, $updatedItem->getSortIndex());
        $this->assertEquals(0, $item->getSortIndex()); // Original unchanged
        $this->assertFalse($updatedItem->isBarInventoryAware());
    }

    public function test_menu_item_is_immutable(): void
    {
        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 0,
        );

        $updatedItem = $item->withPrice(Price::createFromMinor(2000, 'USD'));

        $this->assertNotSame($item, $updatedItem);
    }
}
