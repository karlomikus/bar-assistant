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
    public function test_new_category_is_enabled_by_default(): void
    {
        $category = MenuCategory::create(Name::fromString('Cocktails'));

        $this->assertTrue($category->isEnabled());
    }

    public function test_can_create_disabled_category(): void
    {
        $category = MenuCategory::create(Name::fromString('Drafts'), isEnabled: false);

        $this->assertFalse($category->isEnabled());
    }

    public function test_can_enable_category(): void
    {
        $category = MenuCategory::create(Name::fromString('Specials'), isEnabled: false);
        $enabled = $category->enable();

        $this->assertTrue($enabled->isEnabled());
        $this->assertFalse($category->isEnabled()); // Original is unchanged (immutable)
        $this->assertSame('Specials', $enabled->getName()->toString());
    }

    public function test_can_disable_category(): void
    {
        $category = MenuCategory::create(Name::fromString('Cocktails'));
        $disabled = $category->disable();

        $this->assertFalse($disabled->isEnabled());
        $this->assertTrue($category->isEnabled()); // Original is unchanged (immutable)
    }

    public function test_create_with_items_respects_is_enabled(): void
    {
        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'EUR'),
            sortIndex: 0,
        );

        $category = MenuCategory::createWithItems(
            name: Name::fromString('Hidden'),
            sortIndex: 0,
            items: [$item],
            isEnabled: false,
        );

        $this->assertFalse($category->isEnabled());
        $this->assertCount(1, $category->getItems());
    }

    public function test_create_with_items_defaults_to_enabled(): void
    {
        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'EUR'),
            sortIndex: 0,
        );

        $category = MenuCategory::createWithItems(
            name: Name::fromString('Visible'),
            sortIndex: 0,
            items: [$item],
        );

        $this->assertTrue($category->isEnabled());
    }

    public function test_with_name_preserves_is_enabled(): void
    {
        $category = MenuCategory::create(Name::fromString('Old'), isEnabled: false);
        $renamed = $category->withName(Name::fromString('New'));

        $this->assertFalse($renamed->isEnabled());
        $this->assertSame('New', $renamed->getName()->toString());
    }

    public function test_with_sort_index_preserves_is_enabled(): void
    {
        $category = MenuCategory::create(Name::fromString('Cocktails'), sortIndex: 1, isEnabled: false);
        $resorted = $category->withSortIndex(5);

        $this->assertFalse($resorted->isEnabled());
        $this->assertSame(5, $resorted->getSortIndex());
    }

    public function test_add_item_preserves_is_enabled(): void
    {
        $category = MenuCategory::create(Name::fromString('Hidden'), isEnabled: false);

        $item = MenuItem::forCocktail(
            cocktailId: new CocktailId(1),
            price: Price::createFromMinor(1000, 'EUR'),
            sortIndex: 0,
        );

        $withItem = $category->addItem($item);

        $this->assertFalse($withItem->isEnabled());
        $this->assertCount(1, $withItem->getItems());
    }

    public function test_enable_disable_chainable(): void
    {
        $category = MenuCategory::create(Name::fromString('Cocktails'));

        $result = $category->disable()->enable()->disable();

        $this->assertFalse($result->isEnabled());
    }
}
