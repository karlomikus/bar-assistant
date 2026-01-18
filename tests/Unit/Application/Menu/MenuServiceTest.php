<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Menu;

use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Menu\DTO\CreateMenuCategoryRequest;
use BarAssistant\Application\Menu\DTO\CreateMenuItemRequest;
use BarAssistant\Application\Menu\DTO\CreateMenuRequest;
use BarAssistant\Application\Menu\DTO\UpdateMenuCategoryRequest;
use BarAssistant\Application\Menu\DTO\UpdateMenuItemRequest;
use BarAssistant\Application\Menu\MenuService;
use BarAssistant\Domain\Bar\BarId;
use PHPUnit\Framework\TestCase;
use Tests\Infrastructure\InMemoryMenuRepository;

final class MenuServiceTest extends TestCase
{
    private MenuService $service;
    private InMemoryMenuRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryMenuRepository();
        $this->service = new MenuService($this->repository);
    }

    public function test_can_create_empty_menu(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'summer-menu',
            categories: [],
        );

        $menu = $this->service->createMenu($request);

        $this->assertSame('summer-menu', $menu->getId()->value);
        $this->assertCount(0, $menu->getCategories());
    }

    public function test_can_create_menu_with_categories(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'main-menu',
            categories: [
                new CreateMenuCategoryRequest(
                    name: 'Cocktails',
                    sortIndex: 0,
                    items: [
                        new CreateMenuItemRequest(
                            cocktailId: 1,
                            priceMinor: 1200,
                            priceCurrency: 'USD',
                            sortIndex: 0,
                        ),
                    ],
                ),
                new CreateMenuCategoryRequest(
                    name: 'Shots',
                    sortIndex: 1,
                    items: [],
                ),
            ],
        );

        $menu = $this->service->createMenu($request);

        $this->assertCount(2, $menu->getCategories());
        $this->assertEquals('Cocktails', $menu->getCategories()[0]->getName()->toString());
        $this->assertCount(1, $menu->getCategories()[0]->getItems());
    }

    public function test_can_retrieve_menu(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [],
        );

        $this->service->createMenu($request);
        $menu = $this->service->getMenu('test-menu');

        $this->assertSame('test-menu', $menu->getId()->value);
    }

    public function test_throws_exception_when_menu_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Menu not found');

        $this->service->getMenu('non-existent-menu');
    }

    public function test_can_retrieve_menu_by_bar_id(): void
    {
        $request = new CreateMenuRequest(
            barId: 42,
            menuId: 'bar-menu',
            categories: [],
        );

        $this->service->createMenu($request);
        $menu = $this->service->getMenuByBarId(42);

        $this->assertSame('bar-menu', $menu->getId()->value);
    }

    public function test_throws_exception_when_menu_not_found_by_bar_id(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Menu not found for the given bar');

        $this->service->getMenuByBarId(999);
    }

    public function test_can_add_category_to_menu(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [],
        );

        $menu = $this->service->createMenu($request);
        $this->assertCount(0, $menu->getCategories());

        $categoryRequest = new CreateMenuCategoryRequest(
            name: 'New Category',
            sortIndex: 0,
            items: [],
        );

        $menu = $this->service->addCategoryToMenu($menu, $categoryRequest);

        $this->assertCount(1, $menu->getCategories());
        $this->assertEquals('New Category', $menu->getCategories()[0]->getName()->toString());
    }

    public function test_can_remove_category_from_menu(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [
                new CreateMenuCategoryRequest(
                    name: 'Category 1',
                    sortIndex: 0,
                    items: [],
                ),
                new CreateMenuCategoryRequest(
                    name: 'Category 2',
                    sortIndex: 1,
                    items: [],
                ),
            ],
        );

        $this->service->createMenu($request);

        $menu = $this->service->removeCategoryFromMenu('test-menu', 0);

        $this->assertCount(1, $menu->getCategories());
        $this->assertEquals('Category 2', $menu->getCategories()[0]->getName()->toString());
    }

    public function test_throws_exception_when_removing_nonexistent_category(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [],
        );

        $this->service->createMenu($request);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Category not found');

        $this->service->removeCategoryFromMenu('test-menu', 0);
    }

    public function test_can_update_category(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [
                new CreateMenuCategoryRequest(
                    name: 'Original Name',
                    sortIndex: 0,
                    items: [],
                ),
            ],
        );

        $this->service->createMenu($request);

        $updateRequest = new UpdateMenuCategoryRequest(
            menuId: 'test-menu',
            categoryIndex: 0,
            name: 'Updated Name',
            sortIndex: 5,
        );

        $menu = $this->service->updateCategory($updateRequest);

        $this->assertEquals('Updated Name', $menu->getCategories()[0]->getName()->toString());
        $this->assertEquals(5, $menu->getCategories()[0]->getSortIndex());
    }

    public function test_can_add_item_to_category(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [
                new CreateMenuCategoryRequest(
                    name: 'Cocktails',
                    sortIndex: 0,
                    items: [],
                ),
            ],
        );

        $this->service->createMenu($request);

        $itemRequest = new CreateMenuItemRequest(
            cocktailId: 1,
            priceMinor: 1200,
            priceCurrency: 'USD',
            sortIndex: 0,
        );

        $menu = $this->service->addItemToCategory('test-menu', 0, $itemRequest);

        $this->assertCount(1, $menu->getCategories()[0]->getItems());
    }

    public function test_can_update_menu_item(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [
                new CreateMenuCategoryRequest(
                    name: 'Cocktails',
                    sortIndex: 0,
                    items: [
                        new CreateMenuItemRequest(
                            cocktailId: 1,
                            priceMinor: 1200,
                            priceCurrency: 'USD',
                            sortIndex: 0,
                        ),
                    ],
                ),
            ],
        );

        $this->service->createMenu($request);

        $updateRequest = new UpdateMenuItemRequest(
            menuId: 'test-menu',
            categoryIndex: 0,
            itemIndex: 0,
            priceMinor: 1500,
            priceCurrency: 'USD',
            sortIndex: 5,
        );

        $menu = $this->service->updateMenuItem($updateRequest);
        $item = $menu->getCategories()[0]->getItems()[0];

        $this->assertEquals(1500, $item->getPrice()->getPriceAsMinor());
        $this->assertEquals(5, $item->getSortIndex());
    }

    public function test_can_delete_menu(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [],
        );

        $this->service->createMenu($request);
        $this->assertCount(1, $this->repository->getAll());

        $this->service->deleteMenu('test-menu');

        $this->assertCount(0, $this->repository->getAll());
    }

    public function test_can_create_menu_item_for_ingredient(): void
    {
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [
                new CreateMenuCategoryRequest(
                    name: 'Ingredients',
                    sortIndex: 0,
                    items: [
                        new CreateMenuItemRequest(
                            ingredientId: 5,
                            priceMinor: 500,
                            priceCurrency: 'USD',
                            sortIndex: 0,
                        ),
                    ],
                ),
            ],
        );

        $menu = $this->service->createMenu($request);
        $item = $menu->getCategories()[0]->getItems()[0];

        $this->assertTrue($item->isIngredient());
        $this->assertFalse($item->isCocktail());
    }

    public function test_throws_exception_when_item_has_no_type(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Menu item must reference either a cocktail or an ingredient');

        new CreateMenuItemRequest(
            cocktailId: null,
            ingredientId: null,
            priceMinor: 1000,
            priceCurrency: 'USD',
        );

        // The service would throw when trying to create the item
        $request = new CreateMenuRequest(
            barId: 1,
            menuId: 'test-menu',
            categories: [
                new CreateMenuCategoryRequest(
                    name: 'Category',
                    sortIndex: 0,
                    items: [
                        new CreateMenuItemRequest(
                            cocktailId: null,
                            ingredientId: null,
                            priceMinor: 1000,
                            priceCurrency: 'USD',
                        ),
                    ],
                ),
            ],
        );

        $this->service->createMenu($request);
    }
}
