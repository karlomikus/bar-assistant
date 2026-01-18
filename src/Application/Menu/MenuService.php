<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu;

use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Menu\DTO\CreateMenuCategoryRequest;
use BarAssistant\Application\Menu\DTO\CreateMenuItemRequest;
use BarAssistant\Application\Menu\DTO\CreateMenuRequest;
use BarAssistant\Application\Menu\DTO\UpdateMenuCategoryRequest;
use BarAssistant\Application\Menu\DTO\UpdateMenuItemRequest;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Menu\MenuCategory;
use BarAssistant\Domain\Menu\MenuId;
use BarAssistant\Domain\Menu\MenuItem;
use BarAssistant\Domain\Menu\MenuRepository;
use DomainException;

final readonly class MenuService
{
    public function __construct(
        private MenuRepository $menuRepository,
    ) {
    }

    public function createMenu(CreateMenuRequest $request): Menu
    {
        $menu = Menu::create(
            id: new MenuId($request->menuId),
            barId: new BarId($request->barId),
        );

        foreach ($request->categories as $categoryRequest) {
            $this->addCategoryToMenu($menu, $categoryRequest);
        }

        $this->menuRepository->save($menu);

        return $menu;
    }

    public function getMenu(string $menuId): Menu
    {
        $menu = $this->menuRepository->findById(new MenuId($menuId));

        if ($menu === null) {
            throw new EntityNotFoundException('Menu not found');
        }

        return $menu;
    }

    public function getMenuByBarId(int $barId): Menu
    {
        $menu = $this->menuRepository->findByBarId(new BarId($barId));

        if ($menu === null) {
            throw new EntityNotFoundException('Menu not found for the given bar');
        }

        return $menu;
    }

    public function deleteMenu(string $menuId): void
    {
        $this->menuRepository->delete(new MenuId($menuId));
    }

    public function addCategoryToMenu(Menu $menu, CreateMenuCategoryRequest $request): Menu
    {
        $items = [];
        foreach ($request->items as $itemRequest) {
            $items[] = $this->createMenuItem($itemRequest);
        }

        if ($items) {
            $category = MenuCategory::createWithItems(
                name: Name::fromString($request->name),
                sortIndex: $request->sortIndex,
                items: $items,
            );
        } else {
            $category = MenuCategory::create(
                name: Name::fromString($request->name),
                sortIndex: $request->sortIndex,
            );
        }

        $menu->addCategory($category);
        $this->menuRepository->save($menu);

        return $menu;
    }

    public function removeCategoryFromMenu(string $menuId, int $categoryIndex): Menu
    {
        $menu = $this->getMenu($menuId);
        $categories = $menu->getCategories();

        if (!isset($categories[$categoryIndex])) {
            throw new EntityNotFoundException('Category not found at index ' . $categoryIndex);
        }

        $menu->removeCategory($categories[$categoryIndex]);
        $this->menuRepository->save($menu);

        return $menu;
    }

    public function updateCategory(UpdateMenuCategoryRequest $request): Menu
    {
        $menu = $this->getMenu($request->menuId);
        $categories = $menu->getCategories();

        if (!isset($categories[$request->categoryIndex])) {
            throw new EntityNotFoundException('Category not found at index ' . $request->categoryIndex);
        }

        $category = $categories[$request->categoryIndex];

        if ($request->name !== null) {
            $category = $category->withName(Name::fromString($request->name));
        }

        if ($request->sortIndex !== null) {
            $category = $category->withSortIndex($request->sortIndex);
        }

        // Replace the category in the menu
        $menu->removeCategory($categories[$request->categoryIndex]);
        $menu->addCategory($category);

        $this->menuRepository->save($menu);

        return $menu;
    }

    public function addItemToCategory(string $menuId, int $categoryIndex, CreateMenuItemRequest $request): Menu
    {
        $menu = $this->getMenu($menuId);
        $categories = $menu->getCategories();

        if (!isset($categories[$categoryIndex])) {
            throw new EntityNotFoundException('Category not found at index ' . $categoryIndex);
        }

        $item = $this->createMenuItem($request);
        $updatedCategory = $categories[$categoryIndex]->addItem($item);

        // Replace the category in the menu
        $menu->removeCategory($categories[$categoryIndex]);
        $menu->addCategory($updatedCategory);

        $this->menuRepository->save($menu);

        return $menu;
    }

    public function updateMenuItem(UpdateMenuItemRequest $request): Menu
    {
        $menu = $this->getMenu($request->menuId);
        $categories = $menu->getCategories();

        if (!isset($categories[$request->categoryIndex])) {
            throw new EntityNotFoundException('Category not found at index ' . $request->categoryIndex);
        }

        $category = $categories[$request->categoryIndex];
        $items = $category->getItems();

        if (!isset($items[$request->itemIndex])) {
            throw new EntityNotFoundException('Item not found at index ' . $request->itemIndex);
        }

        $item = $items[$request->itemIndex];

        if ($request->priceMinor !== null && $request->priceCurrency !== null) {
            $price = Price::createFromMinor($request->priceMinor, $request->priceCurrency);
            $item = $item->withPrice($price);
        }

        if ($request->sortIndex !== null) {
            $item = $item->withSortIndex($request->sortIndex);
        }

        // Replace the item in the category and the category in the menu
        $itemsCopy = $items;
        $itemsCopy[$request->itemIndex] = $item;

        $updatedCategory = MenuCategory::createWithItems(
            name: $category->getName(),
            sortIndex: $category->getSortIndex(),
            items: $itemsCopy,
        );

        $menu->removeCategory($category);
        $menu->addCategory($updatedCategory);

        $this->menuRepository->save($menu);

        return $menu;
    }

    private function createMenuItem(CreateMenuItemRequest $request): MenuItem
    {
        $price = Price::createFromMinor($request->priceMinor, $request->priceCurrency);

        if ($request->cocktailId !== null) {
            return MenuItem::forCocktail(
                cocktailId: new CocktailId($request->cocktailId),
                price: $price,
                sortIndex: $request->sortIndex,
            );
        }

        if ($request->ingredientId !== null) {
            return MenuItem::forIngredient(
                ingredientId: new IngredientId($request->ingredientId),
                price: $price,
                sortIndex: $request->sortIndex,
            );
        }

        throw new DomainException('Menu item must reference either a cocktail or an ingredient');
    }
}
