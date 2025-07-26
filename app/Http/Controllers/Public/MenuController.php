<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers\Public;

use Kami\Cocktail\Models\Menu;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Http\Controllers\Controller;
use Kami\Cocktail\Http\Resources\MenuPublicResource;

class MenuController extends Controller
{
    // #[OAT\Get(path: '/public/{barId}/menu', tags: ['Public'], operationId: 'publicBarMenu', description: 'Show a public bar menu details', summary: 'Show public menu', parameters: [
    //     new OAT\Parameter(name: 'barId', in: 'path', required: true, description: 'Bar database id', schema: new OAT\Schema(type: 'number')),
    // ], security: [])]
    // #[BAO\SuccessfulResponse(content: [
    //     new BAO\WrapObjectWithData(MenuPublicResource::class),
    // ])]
    // #[BAO\NotFoundResponse]
    public function show(string $barId): MenuPublicResource
    {
        $menu = Menu::select('menus.*')
            ->where('bars.id', $barId)
            ->where('menus.is_enabled', true)
            ->join('bars', 'bars.id', '=', 'menus.bar_id')
            ->join('menu_cocktails', 'menu_cocktails.menu_id', '=', 'menus.id')
            ->orderBy('menu_cocktails.sort', 'asc')
            ->with(
                'bar.images',
                'menuCocktails.cocktail.ingredients.ingredient',
                'menuCocktails.cocktail.images',
                'menuCocktails.cocktail.bar.shelfIngredients',
                'menuIngredients.ingredient.ancestors',
                'menuIngredients.ingredient.images',
                'menuIngredients.ingredient.bar.shelfIngredients',
            )
            ->firstOrFail();

        return new MenuPublicResource($menu);
    }
}
