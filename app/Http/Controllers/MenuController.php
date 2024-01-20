<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Menu;
use Illuminate\Database\Eloquent\Model;
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

        Model::unguard();

        $menu = Menu::firstOrCreate(['bar_id' => bar()->id])->load('menuCocktails.cocktail');

        Model::reguard();

        return new MenuResource($menu);
    }

    public function show(string $barSlug): MenuPublicResource
    {
        $menu = Menu::where(['slug' => $barSlug])
            ->select('menus.*')
            ->join('bars', 'bars.id', '=', 'menus.bar_id')
            ->join('menu_cocktails', 'menu_cocktails.menu_id', '=', 'menus.id')
            ->orderBy('menu_cocktails.sort', 'asc')
            ->with('menuCocktails.cocktail')
            ->firstOrFail();

        return new MenuPublicResource($menu);
    }

    public function update(Request $request): MenuResource
    {
        if ($request->user()->cannot('update', Menu::class)) {
            abort(403);
        }

        Model::unguard();

        $menu = Menu::firstOrCreate(['bar_id' => bar()->id]);
        $menu->is_enabled = (bool) $request->post('is_enabled');
        if (!$menu->created_at) {
            $menu->created_at = now();
        }
        $menu->updated_at = now();
        $menu->syncCocktails($request->post('cocktails', []));
        $menu->save();

        Model::reguard();

        return new MenuResource($menu);
    }
}
