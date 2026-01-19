<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Menu\Menu;
use BarAssistant\Domain\Menu\MenuId;
use BarAssistant\Domain\Menu\MenuRepository;
use Kami\Cocktail\Models\Menu as Model;

final class EloquentMenuRepository implements MenuRepository
{
    public function save(Menu $menu): Menu
    {
        $model = Model::firstOrNew(['bar_id' => $menu->getBarId()->value]);
        $model->save();

        return self::map($model);
    }

    public function findByBarId(BarId $barId): ?Menu
    {
        $model = Model::first('bar_id', $barId->value);

        return self::map($model);
    }

    private static function map(Model $model): Menu
    {
        return Menu::create(
            id: new MenuId($model->bar->slug),
            barId: new BarId($model->bar_id),
        );
    }
}
