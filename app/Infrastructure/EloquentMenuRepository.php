<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use LogicException;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Menu\MenuId;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Menu\MenuItem;
use Kami\Cocktail\Models\Menu as Model;
use BarAssistant\Domain\Menu\MenuCategory;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Menu\MenuRepository;
use BarAssistant\Domain\Ingredient\IngredientId;
use Kami\Cocktail\Models\MenuCategory as ModelMenuCategory;

final class EloquentMenuRepository implements MenuRepository
{
    public function findById(MenuId $id): ?Menu
    {
        $model = Model::query()
            ->join('bars', 'bars.id', '=', 'menus.bar_id')
            ->where('bars.slug', $id->value)
            ->select('menus.*')
            ->first();

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Menu $menu): Menu
    {
        $model = Model::firstOrNew(['bar_id' => $menu->getBarId()->value]);
        $model->is_enabled = $menu->isEnabled();
        $model->save();

        $bar = $model->bar;
        if ($bar === null) {
            throw new LogicException('Menu must belong to an existing bar');
        }

        // Enabled menus require bars to have slugs
        if (!isset($bar->slug) && $menu->isEnabled()) {
            $bar->generateSlug();
            $bar->save();
        }

        $model->categories()->delete();
        foreach ($menu->getCategories() as $category) {
            $modelCategory = ModelMenuCategory::create([
                'name' => $category->getName(),
                'menu_id' => $model->id,
                'sort' => $category->getSortIndex(),
                'is_enabled' => $category->isEnabled(),
            ]);

            foreach ($category->getItems() as $menuItem) {
                if ($menuItem->isIngredient()) {
                    $modelCategory->menuIngredients()->create([
                       'menu_id' => $model->id,
                        'ingredient_id' => $menuItem->getIngredientId(),
                        'sort' => $menuItem->getSortIndex(),
                        'price' => $menuItem->getPrice()->getAsMinor(),
                        'currency' => $menuItem->getPrice()->getCurrency(),
                        'is_bar_inventory_aware' => $menuItem->isBarInventoryAware(),
                    ]);
                } else {
                    $modelCategory->menuCocktails()->create([
                       'menu_id' => $model->id,
                        'cocktail_id' => $menuItem->getCocktailId(),
                        'sort' => $menuItem->getSortIndex(),
                        'price' => $menuItem->getPrice()->getAsMinor(),
                        'currency' => $menuItem->getPrice()->getCurrency(),
                        'is_bar_inventory_aware' => $menuItem->isBarInventoryAware(),
                    ]);
                }
            }
        }

        return self::map($model);
    }

    public function findByBarId(BarId $barId): ?Menu
    {
        $model = Model::query()
            ->where('bar_id', $barId->value)
            ->with('bar', 'categories.menuCocktails.cocktail', 'categories.menuIngredients.ingredient')
            ->first();

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    private static function map(Model $model): Menu
    {
        $bar = $model->bar;
        if ($bar === null || $bar->slug === null) {
            throw new LogicException('Menu must belong to a bar with a slug');
        }

        $categories = [];
        foreach ($model->categories as $modelCategory) {
            $items = [];
            foreach ($modelCategory->menuCocktails as $menuCocktail) {
                $items[] = MenuItem::forCocktail(
                    cocktailId: new CocktailId($menuCocktail->cocktail_id),
                    price: Price::createFromMinor($menuCocktail->price, $menuCocktail->currency ?? 'EUR'),
                    sortIndex: $menuCocktail->sort,
                    barInventoryAware: $menuCocktail->is_bar_inventory_aware,
                );
            }
            foreach ($modelCategory->menuIngredients as $menuIngredient) {
                $items[] = MenuItem::forIngredient(
                    ingredientId: new IngredientId($menuIngredient->ingredient_id),
                    price: Price::createFromMinor($menuIngredient->price, $menuIngredient->currency ?? 'EUR'),
                    sortIndex: $menuIngredient->sort,
                    barInventoryAware: $menuIngredient->is_bar_inventory_aware,
                );
            }

            $categories[] = MenuCategory::createWithItems(
                name: Name::fromString($modelCategory->name),
                sortIndex: $modelCategory->sort,
                items: $items,
                isEnabled: (bool) $modelCategory->is_enabled,
            );
        }

        $menu = Menu::createWithCategories(
            id: new MenuId($bar->slug),
            barId: new BarId($model->bar_id),
            categories: $categories,
        );

        if ($model->is_enabled) {
            $menu->enable();
        } else {
            $menu->disable();
        }

        return $menu;
    }
}
