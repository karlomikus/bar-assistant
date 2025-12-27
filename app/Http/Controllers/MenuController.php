<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use League\Csv\Writer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Menu;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Http\Requests\MenuRequest;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\Http\Resources\MenuResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\OpenAPI\Schemas\MenuItemRequest;
use Kami\Cocktail\Http\Resources\MenuPublicResource;
use Kami\Cocktail\OpenAPI\Schemas\MenuRequest as SchemasMenuRequest;

class MenuController extends Controller
{
    #[OAT\Get(path: '/menu', tags: ['Menu'], operationId: 'showMenu', description: 'Show a bar menu', summary: 'Show menu', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(MenuResource::class),
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

        $menu = Menu::with(
            'menuCocktails.cocktail.ingredients.ingredient',
            'menuCocktails.cocktail.images',
            'menuCocktails.cocktail.bar.shelfIngredients',
            'menuIngredients.ingredient.ancestors',
            'menuIngredients.ingredient.images',
            'menuIngredients.ingredient.bar.shelfIngredients',
        )->firstOrCreate(['bar_id' => $bar->id]);

        return new MenuResource($menu);
    }

    #[OAT\Get(path: '/explore/menus/{slug}', tags: ['Explore'], operationId: 'publicMenu', description: 'Show a public bar menu details', summary: 'Show public menu', parameters: [
        new OAT\Parameter(name: 'slug', in: 'path', required: true, description: 'Bar database slug', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(MenuPublicResource::class),
    ])]
    #[BAO\NotFoundResponse]
    public function show(string $barSlug): MenuPublicResource
    {
        $menu = Menu::select('menus.*')
            ->where(['slug' => $barSlug])
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

    #[OAT\Post(path: '/menu', tags: ['Menu'], operationId: 'updateMenu', description: 'Update bar menu', summary: 'Update menu', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\MenuRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(MenuResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function update(MenuRequest $request): MenuResource
    {
        if ($request->user()->cannot('update', Menu::class)) {
            abort(403);
        }

        $schemaRequest = SchemasMenuRequest::fromIlluminateRequest($request);

        $ingredients = $schemaRequest->getIngredients();
        $cocktails = $schemaRequest->getCocktails();

        Validator::make(collect($ingredients)->map(fn (MenuItemRequest $mi) => ['id' => $mi->id])->toArray(), [
            '*.id' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
        ])->validate();

        Validator::make(collect($cocktails)->map(fn (MenuItemRequest $mi) => ['id' => $mi->id])->toArray(), [
            '*.id' => [new ResourceBelongsToBar(bar()->id, 'cocktails')],
        ])->validate();

        $menu = Menu::firstOrCreate(['bar_id' => bar()->id]);
        $menu->is_enabled = $schemaRequest->isEnabled;
        if (!$menu->created_at) {
            $menu->created_at = now();
        }
        $menu->updated_at = now();
        $menu->syncItems($schemaRequest->items);
        $menu->save();

        return new MenuResource($menu);
    }

    #[OAT\Get(path: '/menu/export', tags: ['Menu'], operationId: 'exportMenu', summary: 'Export menu', description: 'Export menu as CSV', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new OAT\MediaType(mediaType: 'text/csv', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function export(Request $request): Response
    {
        if ($request->user()->cannot('view', Menu::class)) {
            abort(403);
        }

        $menu = Menu::where('bar_id', bar()->id)
            ->with(
                'menuCocktails.cocktail.ingredients.ingredient',
                'menuCocktails.cocktail.images',
                'menuIngredients.ingredient',
                'menuIngredients.ingredient.ancestors',
                'menuIngredients.ingredient.images',
            )
            ->firstOrFail();

        $records = [
            [
                'type',
                'item',
                'description',
                'category',
                'price',
                'currency',
                'full_price',
            ]
        ];

        /** @var \Kami\Cocktail\Models\ValueObjects\MenuItem $menuItem */
        foreach ($menu->getMenuItems() as $menuItem) {
            $record = [
                $menuItem->type->value,
                e(preg_replace("/\s+/u", " ", $menuItem->name)),
                e($menuItem->description),
                e($menuItem->categoryName),
                $menuItem->price->getMoney()->getAmount()->toFloat(),
                $menuItem->price->getMoney()->getCurrency()->getCurrencyCode(),
                (string) $menuItem->price->getMoney(),
            ];

            $records[] = $record;
        }

        $writer = Writer::createFromString();
        $writer->insertAll($records);

        return new Response($writer->toString(), 200, ['Content-Type' => 'text/csv']);
    }
}
