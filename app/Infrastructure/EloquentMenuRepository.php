<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Menu\MenuCategory;
use BarAssistant\Domain\Menu\MenuId;
use BarAssistant\Domain\Menu\MenuRepository;
use Kami\Cocktail\Models\Menu as Model;
use Kami\Cocktail\Models\MenuCategory as ModelMenuCategory;

final class EloquentMenuRepository implements MenuRepository
{
    public function save(Menu $menu): Menu
    {
        $model = Model::firstOrNew(['bar_id' => $menu->getBarId()->value]);
        $model->save();

        $model->categories()->delete();
        foreach ($menu->getCategories() as $category) {
            $modelCategory = new ModelMenuCategory();
            $modelCategory->name = $category->getName();
            $modelCategory->sort = $category->getSortIndex();
            $model->categories()->save($modelCategory);

            foreach ($category->getItems() as $menuItem) {
                if ($menuItem->isIngredient()) {
                    $modelCategory->menuIngredients()->updateOrCreate([
                        'ingredient_id' => $menuItem->getIngredientId()
                    ], [
                        'sort' => $menuItem->getSortIndex(),
                        'price' => $menuItem->getPrice()->getPriceAsMinor(),
                        'currency' => $menuItem->getPrice()->getCurrency(),
                    ]);
                } else {
                    $modelCategory->menuCocktails()->updateOrCreate([
                        'cocktail_id' => $menuItem->getCocktailId()
                    ], [
                        'sort' => $menuItem->getSortIndex(),
                        'price' => $menuItem->getPrice()->getPriceAsMinor(),
                        'currency' => $menuItem->getPrice()->getCurrency(),
                    ]);
                }
            }
        }

        return self::map($model);
    }

    public function findByBarId(BarId $barId): ?Menu
    {
        $model = Model::firstWhere('bar_id', $barId->value);

        return self::map($model);
    }

    private static function map(Model $model): Menu
    {
        $categories = [];
        foreach ($model->categories as $modelCategory) {
            $items = [];
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

        return $menu;
    }
}
