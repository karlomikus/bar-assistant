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
    #[OAT\Get(path: '/public/{slugOrId}/menu', tags: ['Public'], operationId: 'showPublicBarMenu', description: 'Show a public bar menu details. The bar must have menu enabled.', summary: 'Show public menu', parameters: [
    new OAT\Parameter(name: 'slugOrId', in: 'path', required: true, description: 'Database id or slug of bar', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(MenuPublicResource::class),
    ])]
    #[BAO\NotFoundResponse]
    public function show(string $barSlugOrId): MenuPublicResource
    {
        $menu = Menu::select('menus.*')
            ->where(['slug' => $barSlugOrId])
            ->where('menus.is_enabled', true)
            ->join('bars', 'bars.id', '=', 'menus.bar_id')
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
