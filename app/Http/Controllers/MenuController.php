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
use Kami\Cocktail\Services\CocktailService; 
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\Http\Resources\MenuResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;
use Kami\Cocktail\Http\Resources\MenuPublicResource;

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

        /** @var array<mixed> */
        $items = $request->input('items', []);

        $ingredients = collect($items)->where('type', MenuItemTypeEnum::Ingredient->value)->values()->toArray();
        $cocktails = collect($items)->where('type', MenuItemTypeEnum::Cocktail->value)->values()->toArray();

        Validator::make($ingredients, [
            '*.id' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
        ])->validate();

        Validator::make($cocktails, [
            '*.id' => [new ResourceBelongsToBar(bar()->id, 'cocktails')],
        ])->validate();

        $menu = Menu::firstOrCreate(['bar_id' => bar()->id]);
        $menu->is_enabled = $request->boolean('is_enabled');
        if (!$menu->created_at) {
            $menu->created_at = now();
        }
        $menu->updated_at = now();
        $menu->syncItems($items);
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

    #[OAT\Post(
    path: '/menu/generate-from-shelf',
    tags: ['Menu'],
    operationId: 'generateFromShelf',
    description: 'Generate a menu from ingredients on the bar’s shelf',
    summary: 'Generate menu from shelf',
    parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ]
    )]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(MenuResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function generateFromShelf(Request $request, CocktailService $cocktailService): MenuResource
    {
        $this->authorize('update', Menu::class);

        $bar = bar();
        $ingredientIds = $bar->shelfIngredients
            ->pluck('ingredient_id')
            ->toArray();

        $cocktailIds = $cocktailService
            ->getCocktailsByIngredients($ingredientIds, $bar->id);

        if ($cocktailIds->isEmpty()) {
            abort(404, 'No cocktails could be generated from shelf ingredients.');
        }

        $menu = Menu::firstOrCreate(['bar_id' => $bar->id]);

        $items = $cocktailIds->map(fn (int $id, int $index) => [
            'type'          => MenuItemTypeEnum::Cocktail->value,
            'id'            => $id,
            'price'         => 1,
            'currency'      => 'EUR',
            'category_name' => 'From Shelf',
            'sort'          => $index + 1,
        ])->values()->toArray();

        $menu->syncItems($items);
        $menu->touch();

        return new MenuResource($menu);
    }

}
