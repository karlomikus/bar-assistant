<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Menu;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Menu\MenuId;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Menu\MenuItem;
use BarAssistant\Domain\Menu\MenuCategory;
use BarAssistant\Domain\Cocktail\CocktailId;

final class MenuTest extends TestCase
{
    public function test_can_create_empty_menu(): void
    {
        $menu = Menu::create(
            id: new MenuId('menu-slug'),
            barId: new BarId(1),
        );

        $this->assertSame('menu-slug', $menu->getId()->value);
        $this->assertCount(0, $menu->getCategories());
    }

    public function test_can_create_menu_with_categories(): void
    {
        $category1 = MenuCategory::create(Name::fromString('Cocktails'), sortIndex: 1);
        $category2 = MenuCategory::create(Name::fromString('Shots'), sortIndex: 0);

        $menu = Menu::createWithCategories(
            id: new MenuId('menu-slug'),
            barId: new BarId(1),
            categories: [$category1, $category2],
        );

        $categories = $menu->getCategories();
        $this->assertCount(2, $categories);
        // Categories should be sorted by sortIndex
        $this->assertEquals('Shots', $categories[0]->getName()->toString());
        $this->assertEquals('Cocktails', $categories[1]->getName()->toString());
    }

    public function test_can_add_category_to_menu(): void
    {
        $menu = Menu::create(
            id: new MenuId('menu-slug'),
            barId: new BarId(1),
        );

        $category = MenuCategory::create(Name::fromString('Cocktails'), sortIndex: 0);
        $menu->addCategory($category);

        $this->assertCount(1, $menu->getCategories());
    }

    public function test_can_remove_category_from_menu(): void
    {
        $category1 = MenuCategory::create(Name::fromString('Cocktails'), sortIndex: 0);
        $category2 = MenuCategory::create(Name::fromString('Shots'), sortIndex: 1);

        $menu = Menu::createWithCategories(
            id: new MenuId('menu-slug'),
            barId: new BarId(1),
            categories: [$category1, $category2],
        );

        $menu->removeCategory($category1);

        $categories = $menu->getCategories();
        $this->assertCount(1, $categories);
        $this->assertEquals('Shots', $categories[0]->getName()->toString());
    }

    public function test_categories_are_automatically_sorted_by_sort_index(): void
    {
        $category1 = MenuCategory::create(Name::fromString('Specials'), sortIndex: 5);
        $category2 = MenuCategory::create(Name::fromString('Classics'), sortIndex: 1);
        $category3 = MenuCategory::create(Name::fromString('Shots'), sortIndex: 3);

        $menu = Menu::createWithCategories(
            id: new MenuId('menu-slug'),
            barId: new BarId(1),
            categories: [$category1, $category2, $category3],
        );

        $categories = $menu->getCategories();
        $this->assertEquals('Classics', $categories[0]->getName()->toString());
        $this->assertEquals('Shots', $categories[1]->getName()->toString());
        $this->assertEquals('Specials', $categories[2]->getName()->toString());
    }

    public function test_categories_stay_sorted_after_adding(): void
    {
        $category1 = MenuCategory::create(Name::fromString('Cocktails'), sortIndex: 2);
        $category2 = MenuCategory::create(Name::fromString('Shots'), sortIndex: 3);

        $menu = Menu::createWithCategories(
            id: new MenuId('menu-slug'),
            barId: new BarId(1),
            categories: [$category1, $category2],
        );

        $category3 = MenuCategory::create(Name::fromString('Specials'), sortIndex: 1);
        $menu->addCategory($category3);

        $categories = $menu->getCategories();
        $this->assertEquals('Specials', $categories[0]->getName()->toString());
        $this->assertEquals('Cocktails', $categories[1]->getName()->toString());
        $this->assertEquals('Shots', $categories[2]->getName()->toString());
    }

    public function test_menu_belongs_to_bar(): void
    {
        $barId = new BarId(42);
        $menu = Menu::create(
            id: new MenuId('menu-slug'),
            barId: $barId,
        );

        $this->assertTrue($menu->getBarId()->equals($barId));
    }

    public function test_complete_menu_with_categories_and_items(): void
    {
        // Create menu items
        $item1 = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1200, 'USD'),
            sortIndex: 0,
        );

        $item2 = MenuItem::forCocktail(
            cocktailId: new CocktailId(2),
            price: Price::createFromMinor(1000, 'USD'),
            sortIndex: 1,
        );

        // Create categories
        $cocktailCategory = MenuCategory::createWithItems(
            name: Name::fromString('Classic Cocktails'),
            sortIndex: 0,
            items: [$item1, $item2],
        );

        $shotCategory = MenuCategory::create(
            name: Name::fromString('Shots'),
            sortIndex: 1,
        );

        // Create menu
        $menu = Menu::createWithCategories(
            id: new MenuId('menu-slug'),
            barId: new BarId(1),
            categories: [$cocktailCategory, $shotCategory],
        );

        $this->assertCount(2, $menu->getCategories());
        $this->assertCount(2, $menu->getCategories()[0]->getItems());
    }
}
