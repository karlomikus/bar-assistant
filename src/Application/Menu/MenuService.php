<?php

declare(strict_types=1);

namespace BarAssistant\Application\Menu;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Menu\MenuId;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Menu\MenuItem;
use BarAssistant\Domain\Menu\MenuCategory;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Menu\MenuRepository;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Application\Menu\DTO\CreateMenuRequest;
use BarAssistant\Application\Exception\ValidationException;
use BarAssistant\Application\Menu\DTO\CreateMenuItemRequest;

final readonly class MenuService
{
    public function __construct(
        private MenuRepository $menuRepository,
    ) {
    }

    public function updateOrCreateMenu(CreateMenuRequest $request): Menu
    {
        $menu = $this->menuRepository->findByBarId(new BarId($request->barId));
        if ($menu === null) {
            $menu = Menu::create(
                id: new MenuId($request->menuId),
                barId: new BarId($request->barId),
            );
        }

        if ($request->isEnabled) {
            $menu->enable();
        } else {
            $menu->disable();
        }

        $menu->clearAllCategories();
        foreach ($request->categories as $categoryRequest) {
            $items = [];
            foreach ($categoryRequest->items as $itemRequest) {
                $items[] = $this->createMenuItem($itemRequest);
            }

            $menu->addCategory(MenuCategory::createWithItems(
                name: Name::fromString($categoryRequest->name),
                sortIndex: $categoryRequest->sortIndex,
                items: $items,
                isEnabled: $categoryRequest->isEnabled,
            ));
        }

        $this->menuRepository->save($menu);

        return $menu;
    }

    private function createMenuItem(CreateMenuItemRequest $request): MenuItem
    {
        $price = Price::createFromFloat($request->price, $request->priceCurrency);

        if ($request->cocktailId !== null) {
            return MenuItem::forCocktail(
                cocktailId: new CocktailId($request->cocktailId),
                barInventoryAware: $request->isBarInventoryAware,
                price: $price,
                sortIndex: $request->sortIndex,
            );
        }

        if ($request->ingredientId !== null) {
            return MenuItem::forIngredient(
                ingredientId: new IngredientId($request->ingredientId),
                barInventoryAware: $request->isBarInventoryAware,
                price: $price,
                sortIndex: $request->sortIndex,
            );
        }

        throw new ValidationException('Menu item must reference either a cocktail or an ingredient');
    }
}
