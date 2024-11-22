<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Menu;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\MenuCocktail;
use Kami\Cocktail\Http\Requests\MenuRequest;
use Kami\Cocktail\Http\Resources\MenuResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\MenuPublicResource;

class MenuController extends Controller
{
    #[OAT\Get(path: '/menu', tags: ['Menu'], operationId: 'showMenu', summary: 'Show menu', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Menu::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(Request $request): JsonResource
    {
        if ($request->user()->cannot('view', Menu::class)) {
            abort(403);
        }

        $bar = bar();
        if (!$bar->slug) {
            $bar->generateSlug();
            $bar->save();
        }

        $menu = Menu::with('menuCocktails.cocktail.ingredients.ingredient')->firstOrCreate(['bar_id' => $bar->id]);

        return new MenuResource($menu);
    }

    #[OAT\Get(path: '/explore/menus/{slug}', tags: ['Explore'], operationId: 'publicMenu', summary: 'Show public bar menu', parameters: [
        new OAT\Parameter(name: 'slug', in: 'path', required: true, description: 'Bar database slug', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\MenuExplore::class),
    ])]
    #[BAO\NotFoundResponse]
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

    #[OAT\Post(path: '/menu', tags: ['Menu'], operationId: 'updateMenu', summary: 'Update menu', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\MenuRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Menu::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function update(MenuRequest $request): MenuResource
    {
        if ($request->user()->cannot('update', Menu::class)) {
            abort(403);
        }

        $menu = Menu::firstOrCreate(['bar_id' => bar()->id]);
        $menu->is_enabled = $request->boolean('is_enabled');
        if (!$menu->created_at) {
            $menu->created_at = now();
        }
        $menu->updated_at = now();
        $menu->syncCocktails($request->input('cocktails', []));
        $menu->save();

        return new MenuResource($menu);
    }

    #[OAT\Get(path: '/menu/export', tags: ['Menu'], operationId: 'exportMenu', summary: 'Export menu', description: 'Export menu as CSV', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\MediaType(mediaType: 'text/csv', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function export(Request $request): Response
    {
        if ($request->user()->cannot('view', Menu::class)) {
            abort(403);
        }

        $records = [
            [
                'cocktail',
                'ingredients',
                'category',
                'price',
                'currency',
                'full_price',
            ]
        ];

        $cocktails = MenuCocktail::query()
            ->with('cocktail.ingredients.ingredient')
            ->join('menus', 'menus.id', '=', 'menu_cocktails.menu_id')
            ->where('menus.bar_id', bar()->id)
            ->get();

        foreach ($cocktails as $menuCocktail) {
            $record = [
                e(preg_replace("/\s+/u", " ", $menuCocktail->cocktail->name)),
                e($menuCocktail->cocktail->getIngredientNames()->implode(', ')),
                e($menuCocktail->category_name),
                $menuCocktail->getMoney()->getAmount()->toFloat(),
                $menuCocktail->getMoney()->getCurrency()->getCurrencyCode(),
                (string) $menuCocktail->getMoney(),
            ];

            $records[] = $record;
        }

        $writer = \League\Csv\Writer::createFromString();
        $writer->insertAll($records);

        return new Response($writer->toString(), 200, ['Content-Type' => 'text/csv']);
    }
}
