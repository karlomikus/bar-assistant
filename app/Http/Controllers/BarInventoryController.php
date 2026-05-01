<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Application\Bar\InventoryService;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use Kami\Cocktail\Http\Requests\ShelfIngredientsRequest;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;
use BarAssistant\Application\Bar\DTO\BarInventoryStockChangeRequest;

class BarInventoryController extends Controller
{
    #[OAT\Get(path: '/bars/{id}/inventory/ingredients', tags: ['Bar inventory'], operationId: 'listBarInventoryIngredients', summary: 'List bar inventory ingredients', description: 'Ingredients that bar has in it\'s inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(IngredientBasicResource::class),
    ])]
    public function inventoryIngredients(Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $ingredientIds = $bar->shelfIngredients->pluck('ingredient_id');

        $ingredients = Ingredient::whereIn('id', $ingredientIds)->orderBy('name')->paginate($request->input('per_page', 100));

        return IngredientBasicResource::collection($ingredients->withQueryString());
    }

    #[OAT\Post(path: '/bars/{id}/inventory/ingredients/batch-store', tags: ['Bar inventory'], operationId: 'batchStoreBarInventoryIngredients', description: 'Save multiple ingredients to bar inventory', summary: 'Save bar ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'ingredients', type: 'array', items: new OAT\Items(type: 'integer')),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function batchStoreBarIngredients(ShelfIngredientsRequest $request, InventoryService $barInventoryService, int $id): Response
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('manageShelf', $bar)) {
            abort(403);
        }

        $ingredientIds = $request->post('ingredients', []);
        Validator::make($ingredientIds, [
            '*' => [new ResourceBelongsToBar($bar->id, 'ingredients')],
        ])->validate();

        $barInventoryService->putMultipleIngredientsInStock(new BarInventoryStockChangeRequest($bar->id, $ingredientIds));

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/bars/{id}/inventory/ingredients/batch-delete', tags: ['Bar inventory'], operationId: 'batchDeleteBarInventoryIngredients', description: 'Delete multiple ingredients from bar inventory', summary: 'Delete bar ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'ingredients', type: 'array', items: new OAT\Items(type: 'integer')),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function batchDeleteBarIngredients(ShelfIngredientsRequest $request, InventoryService $barInventoryService, int $id): Response
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('manageShelf', $bar)) {
            abort(403);
        }

        $ingredientIds = $request->post('ingredients', []);
        Validator::make($ingredientIds, [
            '*' => [new ResourceBelongsToBar($bar->id, 'ingredients')],
        ])->validate();

        $barInventoryService->removeMultipleIngredientsFromStock(new BarInventoryStockChangeRequest($bar->id, $ingredientIds));

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/bars/{id}/inventory/cocktails', tags: ['Bar inventory'], operationId: 'listBarInventoryCocktails', summary: 'List bar inventory cocktails', description: 'Cocktails that the bar can make with ingredients in stock', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(CocktailBasicResource::class),
    ])]
    public function barCocktails(CocktailService $cocktailRepo, Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $cocktailIds = $cocktailRepo->getCocktailsByIngredients(
            $bar->shelfIngredients->pluck('ingredient_id')->toArray(),
            $bar->id,
        );

        $cocktails = Cocktail::whereIn('id', $cocktailIds)->with('ingredients.ingredient')->paginate($request->input('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/bars/{id}/inventory/ingredients/recommend', tags: ['Bar inventory'], operationId: 'recommendBarIngredients', description: 'Shows a list of ingredients that will increase total bar shelf cocktails when added to bar shef', summary: 'Recommend bar ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientRecommend::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function recommendBarIngredients(Request $request, IngredientService $ingredientRepo, int $id): JsonResponse
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $possibleIngredients = $ingredientRepo->getIngredientsForPossibleCocktails($bar->id, $bar->shelfIngredients->pluck('ingredient_id')->toArray());

        return response()->json(['data' => $possibleIngredients]);
    }
}
