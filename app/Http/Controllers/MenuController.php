<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\Menu;
use Kami\Cocktail\Http\Requests\MenuRequest;
use Kami\Cocktail\Http\Resources\MenuResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\MenuPublicResource;

class MenuController extends Controller
{
    public function index(Request $request): JsonResource
    {
        if ($request->user()->cannot('view', Menu::class)) {
            abort(403);
        }

        $bar = bar();
        if (!$bar->slug) {
            $bar->slug = Str::slug($bar->name);
            $bar->save();
        }

        $menu = Menu::with('menuCocktails.cocktail.ingredients.ingredient')->firstOrCreate(['bar_id' => $bar->id]);

        return new MenuResource($menu);
    }

    public function show(string $barSlug): MenuPublicResource
    {
        $menu = Menu::select('menus.*')
            ->where(['slug' => $barSlug])
            ->where('menus.is_enabled', true)
            ->join('bars', 'bars.id', '=', 'menus.bar_id')
            ->join('menu_cocktails', 'menu_cocktails.menu_id', '=', 'menus.id')
            ->orderBy('menu_cocktails.sort', 'asc')
            ->with('menuCocktails.cocktail')
            ->firstOrFail();

        return new MenuPublicResource($menu);
    }

    public function update(MenuRequest $request): MenuResource
    {
        if ($request->user()->cannot('update', Menu::class)) {
            abort(403);
        }

        $menu = Menu::firstOrCreate(['bar_id' => bar()->id]);
        $menu->is_enabled = (bool) $request->post('is_enabled');
        if (!$menu->created_at) {
            $menu->created_at = now();
        }
        $menu->updated_at = now();
        $menu->syncCocktails($request->post('cocktails', []));
        $menu->save();

        return new MenuResource($menu);
    }
}
