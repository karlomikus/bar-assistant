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
use BarAssistant\Application\Menu\MenuService;
use Kami\Cocktail\Http\Resources\MenuResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\Enums\MenuItemTypeEnum;
use BarAssistant\Application\Menu\DTO\CreateMenuRequest;
use BarAssistant\Application\Menu\DTO\CreateMenuItemRequest;
use BarAssistant\Application\Menu\DTO\CreateMenuCategoryRequest;
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

        $menu = Menu::firstOrCreate(['bar_id' => $bar->id]);

        return new MenuResource($menu);
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
    public function update(MenuService $service, MenuRequest $request): Response
    {
        if ($request->user()->cannot('update', Menu::class)) {
            abort(403);
        }
        $bar = bar();

        $bodyRequest = SchemasMenuRequest::fromIlluminateRequest($request);

        $validateCocktailIds = [];
        $validateIngredientIds = [];
        $categories = [];
        foreach ($bodyRequest->categories as $bodyCategory) {
            $items = [];
            foreach ($bodyCategory->items as $bodyMenuItem) {
                if ($bodyMenuItem->type === MenuItemTypeEnum::Cocktail) {
                    $validateCocktailIds[] = $bodyMenuItem->id;
                } else {
                    $validateIngredientIds[] = $bodyMenuItem->id;
                }
                $items[] = new CreateMenuItemRequest(
                    cocktailId: $bodyMenuItem->type === MenuItemTypeEnum::Cocktail ? $bodyMenuItem->id : null,
                    ingredientId: $bodyMenuItem->type === MenuItemTypeEnum::Ingredient ? $bodyMenuItem->id : null,
                    price: $bodyMenuItem->price,
                    priceCurrency: $bodyMenuItem->currency,
                    sortIndex: $bodyMenuItem->sort,
                    isBarInventoryAware: $bodyMenuItem->isBarInventoryAware,
                );
            }
            $categories[] = new CreateMenuCategoryRequest(name: $bodyCategory->name, sortIndex: $bodyCategory->sort, items: $items, isEnabled: $bodyCategory->isEnabled);
        }

        Validator::make($validateCocktailIds, [
            '*' => [new ResourceBelongsToBar($bar->id, 'cocktails')],
        ])->validate();

        Validator::make($validateIngredientIds, [
            '*' => [new ResourceBelongsToBar($bar->id, 'ingredients')],
        ])->validate();

        $service->updateOrCreateMenu(new CreateMenuRequest(
            barId: $bar->id,
            menuId: $bar->slug,
            categories: $categories,
            isEnabled: $bodyRequest->isEnabled,
        ));

        return new Response(status: 204);
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

        $menu = Menu::where('bar_id', bar()->id)->firstOrFail();

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

        foreach ($menu->categories as $menuCategory) {
            /** @var \Kami\Cocktail\Models\ValueObjects\MenuItem $menuItem */
            foreach ($menuCategory->getMenuItems() as $menuItem) {
                $record = [
                    $menuItem->type->value,
                    e(preg_replace("/\s+/u", " ", $menuItem->name)),
                    e($menuItem->description),
                    e($menuCategory->name),
                    $menuItem->price->getMoney()->getAmount()->toFloat(),
                    $menuItem->price->getMoney()->getCurrency()->getCurrencyCode(),
                    (string) $menuItem->price->getMoney(),
                ];
                $records[] = $record;
            }
        }

        $writer = Writer::fromString();
        $writer->insertAll($records);

        return new Response($writer->toString(), 200, ['Content-Type' => 'text/csv']);
    }
}
