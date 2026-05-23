<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Menu;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Menu\MenuItem;
use BarAssistant\Domain\Menu\MenuCategory;
use BarAssistant\Domain\Cocktail\CocktailId;

final class MenuCategoryTest extends TestCase
{
    public function test_can_create_empty_category(): void
    {
        $category = MenuCategory::create(
            name: Name::fromString('Cocktails'),
            sortIndex: 0,
        );

        $this->assertEquals('Cocktails', $category->getName()->toString());
        $this->assertEquals(0, $category->getSortIndex());
        $this->assertCount(0, $category->getItems());
    }

    public function test_can_create_category_with_items(): void
    {
        $item1 = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 1,
        );

        $item2 = MenuItem::forCocktail(
            cocktailId: new CocktailId(2),
            price: Price::createFromMinor(1200, 'USD'),
            sortIndex: 0,
        );

        $category = MenuCategory::createWithItems(
            name: Name::fromString('Classics'),
            sortIndex: 0,
            items: [$item1, $item2],
        );

        $items = $category->getItems();
        $this->assertCount(2, $items);
        // Items should be sorted by sortIndex
        $this->assertEquals(0, $items[0]->getSortIndex());
        $this->assertEquals(1, $items[1]->getSortIndex());
    }

    public function test_can_add_item_to_category(): void
    {
        $category = MenuCategory::create(
            name: Name::fromString('Cocktails'),
            sortIndex: 0,
        );

        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 0,
        );

        $updatedCategory = $category->addItem($item);

        $this->assertCount(0, $category->getItems()); // Original unchanged
        $this->assertCount(1, $updatedCategory->getItems());
    }

    public function test_items_are_automatically_sorted_by_sort_index(): void
    {
        $item1 = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 5,
        );

        $item2 = MenuItem::forCocktail(
            cocktailId: new CocktailId(2),
            price: Price::createFromMinor(1200, 'USD'),
            sortIndex: 1,
        );

        $item3 = MenuItem::forCocktail(
            cocktailId: new CocktailId(3),
            price: Price::createFromMinor(1500, 'USD'),
            sortIndex: 3,
        );

        $category = MenuCategory::createWithItems(
            name: Name::fromString('Cocktails'),
            sortIndex: 0,
            items: [$item1, $item2, $item3],
        );

        $items = $category->getItems();
        $this->assertEquals(1, $items[0]->getSortIndex());
        $this->assertEquals(3, $items[1]->getSortIndex());
        $this->assertEquals(5, $items[2]->getSortIndex());
    }

    public function test_can_update_category_name(): void
    {
        $category = MenuCategory::create(
            name: Name::fromString('Cocktails'),
            sortIndex: 0,
        );

        $updatedCategory = $category->withName(Name::fromString('Classic Cocktails'));

        $this->assertEquals('Cocktails', $category->getName()->toString());
        $this->assertEquals('Classic Cocktails', $updatedCategory->getName()->toString());
    }

    public function test_can_update_sort_index(): void
    {
        $category = MenuCategory::create(
            name: Name::fromString('Cocktails'),
            sortIndex: 0,
        );

        $updatedCategory = $category->withSortIndex(5);

        $this->assertEquals(0, $category->getSortIndex());
        $this->assertEquals(5, $updatedCategory->getSortIndex());
    }

    public function test_category_is_immutable(): void
    {
        $category = MenuCategory::create(
            name: Name::fromString('Cocktails'),
            sortIndex: 0,
        );

        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 0,
        );

        $updatedCategory = $category->addItem($item);

        $this->assertNotSame($category, $updatedCategory);
    }
}
