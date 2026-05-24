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

        $possibleIngredients = $ingredientRepo->getIngredientsOrderedByUnlockedCocktails($bar->id, $bar->shelfIngredients->pluck('ingredient_id')->toArray());

        return response()->json(['data' => array_slice($possibleIngredients, 0, 15)]);
    }

    #[OAT\Get(path: '/bars/{id}/inventory/ingredients/{idOrSlug}/extra', tags: ['Bar inventory'], operationId: 'extraIngredients', description: 'Show a list of extra cocktails you can make if you add given ingredient to bar inventory', summary: 'Extra cocktails', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id of a bar', schema: new OAT\Schema(type: 'integer')),
        new OAT\Parameter(name: 'idOrSlug', in: 'path', required: true, description: 'Database id or slug of an ingredient', schema: new OAT\Schema(type: 'integer')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CocktailBasicResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function extra(Request $request, CocktailService $cocktailRepo, int $id, string $idOrSlug): JsonResponse
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $ingredient = Ingredient::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->where('bar_id', $id)
            ->firstOrFail();

        $barShelfIngredients = $bar->shelfIngredients->pluck('ingredient_id');
        $currentShelfCocktails = $cocktailRepo->getCocktailsByIngredients($barShelfIngredients->toArray(), $ingredient->bar_id)->values();
        $extraShelfCocktails = $cocktailRepo->getCocktailsByIngredients($barShelfIngredients->push($ingredient->id)->toArray(), $ingredient->bar_id)->values();

        if ($currentShelfCocktails->count() === $extraShelfCocktails->count()) {
            return response()->json(['data' => []]);
        }

        $extraCocktails = Cocktail::whereIn('id', $extraShelfCocktails->diff($currentShelfCocktails)->values())->where('bar_id', '=', $ingredient->bar_id)->get();

        return response()->json([
            'data' => $extraCocktails->map(fn (Cocktail $cocktail) => [
                'id' => $cocktail->id,
                'slug' => $cocktail->slug,
                'name' => $cocktail->name,
            ])
        ]);
    }
}
