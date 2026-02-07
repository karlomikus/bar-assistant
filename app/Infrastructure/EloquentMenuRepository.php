<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Menu\MenuItem;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Menu\MenuId;
use Kami\Cocktail\Models\Menu as Model;
use BarAssistant\Domain\Menu\MenuCategory;
use BarAssistant\Domain\Menu\MenuRepository;
use Kami\Cocktail\Models\MenuCategory as ModelMenuCategory;

final class EloquentMenuRepository implements MenuRepository
{
    public function findById(MenuId $id): ?Menu
    {
        $model = Model::query()
            ->join('bars', 'bars.id', '=', 'menus.bar_id')
            ->where('bars.slug', $id->value)
            ->select('menus.*')
            ->with('bar', 'categories.menuCocktails.cocktail', 'categories.menuIngredients.ingredient')
            ->first();

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Menu $menu): Menu
    {
        $model = Model::firstOrNew(['bar_id' => $menu->getBarId()->value])->load('bar', 'categories.menuCocktails', 'categories.menuIngredients');
        $model->is_enabled = $menu->isEnabled();
        $model->save();

        // Enabled menus require bars to have slugs
        if (!isset($model->bar->slug) && $menu->isEnabled()) {
            $model->bar->generateSlug();
            $model->bar->save();
        }

        foreach ($menu->getCategories() as $category) {
            $modelCategory = ModelMenuCategory::firstOrCreate([
                'name' => $category->getName(),
                'menu_id' => $model->id,
            ], [
                'sort' => $category->getSortIndex(),
            ]);

            foreach ($category->getItems() as $menuItem) {
                if ($menuItem->isIngredient()) {
                    $modelCategory->menuIngredients()->updateOrCreate([
                        'ingredient_id' => $menuItem->getIngredientId(),
                    ], [
                        'sort' => $menuItem->getSortIndex(),
                        'price' => $menuItem->getPrice()->getAsMinor(),
                        'currency' => $menuItem->getPrice()->getCurrency(),
                        'is_bar_inventory_aware' => $menuItem->isBarInventoryAware(),
                    ]);
                } else {
                    $modelCategory->menuCocktails()->updateOrCreate([
                        'cocktail_id' => $menuItem->getCocktailId(),
                    ], [
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
            );
        }

        $menu = Menu::createWithCategories(
            id: new MenuId($model->bar->slug),
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
