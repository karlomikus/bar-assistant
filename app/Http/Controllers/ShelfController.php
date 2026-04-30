<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Application\Bar\InventoryService;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use BarAssistant\Application\Bar\MemberInventoryService;
use Kami\Cocktail\Http\Requests\ShelfIngredientsRequest;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;
use Kami\Cocktail\Http\Resources\MemberInventoryResource;
use BarAssistant\Application\Bar\DTO\DeleteInventoryRequest;
use BarAssistant\Application\Bar\DTO\UpdateInventoryNameRequest;
use Kami\Cocktail\Models\MemberInventory as ModelMemberInventory;
use BarAssistant\Application\Bar\DTO\CreateMemberInventoryRequest;
use BarAssistant\Application\Bar\DTO\BarInventoryStockChangeRequest;
use BarAssistant\Application\Bar\DTO\MemberInventoryStockChangeRequest;

class ShelfController extends Controller
{
    private const string LEGACY_MEMBER_INVENTORY_NAME = 'My Shelf';

    #[OAT\Get(path: '/members/{id}/inventories', tags: ['Users: Shelf'], operationId: 'listMemberInventories', summary: 'List member inventories', description: 'List all inventories owned by the current member', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(MemberInventoryResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function inventories(Request $request, int $id): JsonResource
    {
        $barMembership = $this->ownedBarMembership($request, $id);

        return MemberInventoryResource::collection(
            $barMembership->memberInventories()
                ->withCount('inventoryIngredients')
                ->orderBy('name')
                ->get()
        );
    }

    #[OAT\Post(path: '/members/{id}/inventories', tags: ['Users: Shelf'], operationId: 'createMemberInventory', summary: 'Create member inventory', description: 'Create a new inventory for the current member', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', required: ['name'], properties: [
                new OAT\Property(property: 'name', type: 'string', example: 'Back Bar'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the inventories collection', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function storeInventory(MemberInventoryService $memberInventoryService, Request $request, int $id): Response
    {
        $barMembership = $this->ownedBarMembership($request, $id);
        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        $memberInventoryService->createInventory(new CreateMemberInventoryRequest(
            memberId: $barMembership->id,
            userId: $request->user()->id,
            name: $validated['name'],
        ));

        return new Response(status: 201, headers: ['Location' => sprintf('/members/%d/inventories', $id)]);
    }

    #[OAT\Patch(path: '/members/{id}/inventories/{inventoryId}', tags: ['Users: Shelf'], operationId: 'updateMemberInventory', summary: 'Update member inventory', description: 'Rename a member inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'inventoryId', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', required: ['name'], properties: [
                new OAT\Property(property: 'name', type: 'string', example: 'Back Bar'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function updateInventoryName(MemberInventoryService $memberInventoryService, Request $request, int $id, int $inventoryId): Response
    {
        $memberInventory = $this->ownedMemberInventory($request, $id, $inventoryId);
        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        $memberInventoryService->updateInventoryName(new UpdateInventoryNameRequest(
            inventoryId: $memberInventory->id,
            userId: $request->user()->id,
            name: $validated['name'],
        ));

        return new Response(null, 204);
    }

    #[OAT\Delete(path: '/members/{id}/inventories/{inventoryId}', tags: ['Users: Shelf'], operationId: 'deleteMemberInventory', summary: 'Delete member inventory', description: 'Delete a member inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'inventoryId', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function deleteInventory(MemberInventoryService $memberInventoryService, Request $request, int $id, int $inventoryId): Response
    {
        $memberInventory = $this->ownedMemberInventory($request, $id, $inventoryId);

        $memberInventoryService->deleteInventory(new DeleteInventoryRequest(
            inventoryId: $memberInventory->id,
        ));

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/members/{id}/inventories/{inventoryId}/ingredients', tags: ['Users: Shelf'], operationId: 'listMemberInventoryIngredients', summary: 'List member inventory ingredients', description: 'List ingredients stored in a member inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'inventoryId', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(IngredientBasicResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function inventoryIngredients(Request $request, int $id, int $inventoryId): JsonResource
    {
        $memberInventory = $this->ownedMemberInventory($request, $id, $inventoryId);

        $ingredients = Ingredient::whereIn('id', $this->memberInventoryIngredientIds($memberInventory))
            ->orderBy('name')
            ->paginate($request->input('per_page', 100));

        return IngredientBasicResource::collection($ingredients->withQueryString());
    }

    #[OAT\Post(path: '/members/{id}/inventories/{inventoryId}/ingredients/batch-store', tags: ['Users: Shelf'], operationId: 'batchStoreMemberInventoryIngredients', summary: 'Save member inventory ingredients', description: 'Save multiple ingredients to a member inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'inventoryId', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
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
    public function batchStoreInventoryIngredients(MemberInventoryService $memberInventoryService, ShelfIngredientsRequest $request, int $id, int $inventoryId): Response
    {
        $this->ownedMemberInventory($request, $id, $inventoryId);
        $ingredientIds = $request->post('ingredients');

        Validator::make($ingredientIds, [
            '*' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
        ])->validate();

        $memberInventoryService->putMultipleIngredientsInStock(new MemberInventoryStockChangeRequest(
            ingredientIds: $ingredientIds,
            inventoryId: $inventoryId,
        ));

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/members/{id}/inventories/{inventoryId}/ingredients/batch-delete', tags: ['Users: Shelf'], operationId: 'batchDeleteMemberInventoryIngredients', summary: 'Delete member inventory ingredients', description: 'Delete multiple ingredients from a member inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'inventoryId', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
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
    public function batchDeleteInventoryIngredients(MemberInventoryService $memberInventoryService, ShelfIngredientsRequest $request, int $id, int $inventoryId): Response
    {
        $this->ownedMemberInventory($request, $id, $inventoryId);
        $ingredientIds = $request->post('ingredients');

        Validator::make($ingredientIds, [
            '*' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
        ])->validate();

        $memberInventoryService->removeMultipleIngredientsFromStock(new MemberInventoryStockChangeRequest(
            ingredientIds: $ingredientIds,
            inventoryId: $inventoryId,
        ));

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/members/{id}/inventories/{inventoryId}/cocktails', tags: ['Users: Shelf'], operationId: 'listMemberInventoryCocktails', summary: 'List member inventory cocktails', description: 'List cocktails makeable from a member inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'inventoryId', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(CocktailBasicResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function inventoryCocktails(CocktailService $cocktailRepo, Request $request, int $id, int $inventoryId): JsonResource
    {
        $memberInventory = $this->ownedMemberInventory($request, $id, $inventoryId);

        $cocktailIds = $cocktailRepo->getCocktailsByIngredients(
            $this->memberInventoryIngredientIds($memberInventory),
            bar()->id,
            null,
        );

        $cocktails = Cocktail::whereIn('id', $cocktailIds)
            ->with('ingredients.ingredient')
            ->paginate($request->input('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/members/{id}/inventories/{inventoryId}/recommend', tags: ['Users: Shelf'], operationId: 'recommendMemberInventoryIngredients', summary: 'Recommend member inventory ingredients', description: 'Recommend ingredients for a member inventory', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'inventoryId', in: 'path', required: true, schema: new OAT\Schema(type: 'integer')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientRecommend::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function inventoryRecommend(Request $request, IngredientService $ingredientRepo, int $id, int $inventoryId): \Illuminate\Http\JsonResponse
    {
        $memberInventory = $this->ownedMemberInventory($request, $id, $inventoryId);

        $possibleIngredients = $ingredientRepo->getIngredientsForPossibleCocktails(
            bar()->id,
            $this->memberInventoryIngredientIds($memberInventory),
        );

        return response()->json(['data' => $possibleIngredients]);
    }

    #[OAT\Get(path: '/members/{id}/ingredients', tags: ['Users: Shelf'], operationId: 'listUserIngredients', summary: 'List user ingredients', description: 'Ingredients that user saved to their shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(IngredientBasicResource::class),
    ])]
    public function ingredients(Request $request, int $id): JsonResource
    {
        $barMembership = $this->ownedBarMembership($request, $id);
        $memberInventory = $this->legacyMemberInventory($barMembership);

        $userIngredientIds = $memberInventory?->inventoryIngredients->pluck('ingredient_id') ?? collect();

        $ingredients = Ingredient::whereIn('id', $userIngredientIds)->orderBy('name')->paginate($request->input('per_page', 100));

        return IngredientBasicResource::collection($ingredients->withQueryString());
    }

    #[OAT\Get(path: '/members/{id}/cocktails', tags: ['Users: Shelf'], operationId: 'listUserShelfCocktails', summary: 'List shelf cocktails', description: 'Cocktails that the user can make with ingredients on their shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(CocktailBasicResource::class),
    ])]
    public function cocktails(CocktailService $cocktailRepo, Request $request, int $id): JsonResource
    {
        $barMembership = $this->ownedBarMembership($request, $id);
        $memberInventory = $this->legacyMemberInventory($barMembership);

        $ingredientIds = $memberInventory?->inventoryIngredients->pluck('ingredient_id')->toArray() ?? [];

        $cocktailIds = $cocktailRepo->getCocktailsByIngredients(
            $ingredientIds,
            bar()->id,
            null,
        );

        $cocktails = Cocktail::whereIn('id', $cocktailIds)->with('ingredients.ingredient')->paginate($request->input('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/members/{id}/cocktails/favorites', tags: ['Users: Shelf'], operationId: 'listUserFavoriteCocktails', description: 'Show a list of cocktails user has favorited', summary: 'List favorites', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(CocktailBasicResource::class),
    ])]
    public function favorites(Request $request, int $id): JsonResource
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $cocktailIds = CocktailFavorite::where('bar_membership_id', $barMembership->id)->pluck('cocktail_id');

        $cocktails = Cocktail::whereIn('id', $cocktailIds)->with('ingredients.ingredient')->paginate($request->input('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Post(path: '/members/{id}/ingredients/batch-store', tags: ['Users: Shelf'], operationId: 'batchStoreUserIngredients', description: 'Save multiple ingredients to user shelf', summary: 'Save user ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
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
    public function batchStore(MemberInventoryService $memberInventoryService, ShelfIngredientsRequest $request, int $id): Response
    {
        $barMembership = $this->ownedBarMembership($request, $id);
        $ingredientIds = $request->post('ingredients');

        Validator::make($ingredientIds, [
            '*' => [new ResourceBelongsToBar($barMembership->bar_id, 'ingredients')],
        ])->validate();

        $memberInventory = $this->legacyMemberInventory($barMembership);
        if ($memberInventory === null) {
            $memberInventoryService->createInventory(new CreateMemberInventoryRequest(
                memberId: $barMembership->id,
                userId: $request->user()->id,
                name: self::LEGACY_MEMBER_INVENTORY_NAME,
            ));

            $memberInventory = $this->legacyMemberInventory($barMembership);
            if ($memberInventory === null) {
                abort(500);
            }
        }

        $memberInventoryService->putMultipleIngredientsInStock(new MemberInventoryStockChangeRequest(
            ingredientIds: $ingredientIds,
            inventoryId: $memberInventory->id,
        ));

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/members/{id}/ingredients/batch-delete', tags: ['Users: Shelf'], operationId: 'batchDeleteUserIngredients', description: 'Delete multiple ingredients from user shelf', summary: 'Delete user ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
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
    public function batchDelete(MemberInventoryService $memberInventoryService, ShelfIngredientsRequest $request, int $id): Response
    {
        $barMembership = $this->ownedBarMembership($request, $id);
        $ingredientIds = $request->post('ingredients');

        Validator::make($ingredientIds, [
            '*' => [new ResourceBelongsToBar($barMembership->bar_id, 'ingredients')],
        ])->validate();

        $memberInventory = $this->legacyMemberInventory($barMembership);
        if ($memberInventory === null) {
            return new Response(null, 204);
        }

        $memberInventoryService->removeMultipleIngredientsFromStock(new MemberInventoryStockChangeRequest(
            ingredientIds: $ingredientIds,
            inventoryId: $memberInventory->id,
        ));

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/members/{id}/ingredients/recommend', tags: ['Users: Shelf'], operationId: 'recommendIngredients', description: 'Shows a list of ingredients that will increase total shelf cocktails when added to user shef', summary: 'Recommend user ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientRecommend::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function recommend(Request $request, IngredientService $ingredientRepo, int $id): \Illuminate\Http\JsonResponse
    {
        $barMembership = $this->ownedBarMembership($request, $id);
        $memberInventory = $this->legacyMemberInventory($barMembership);

        $possibleIngredients = $ingredientRepo->getIngredientsForPossibleCocktails(
            bar()->id,
            $memberInventory?->inventoryIngredients->pluck('ingredient_id')->toArray() ?? [],
        );

        return response()->json(['data' => $possibleIngredients]);
    }

    #[OAT\Get(path: '/bars/{id}/ingredients', tags: ['Bars: Shelf'], operationId: 'listBarShelfIngredients', summary: 'List bar shelf ingredients', description: 'Ingredients that bar has in it\'s shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(IngredientBasicResource::class),
    ])]
    public function barIngredients(Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $ingredientIds = $bar->shelfIngredients->pluck('ingredient_id');

        $ingredients = Ingredient::whereIn('id', $ingredientIds)->orderBy('name')->paginate($request->input('per_page', 100));

        return IngredientBasicResource::collection($ingredients->withQueryString());
    }

    #[OAT\Post(path: '/bars/{id}/ingredients/batch-store', tags: ['Bars: Shelf'], operationId: 'batchStoreBarShelfIngredients', description: 'Save multiple ingredients to bar shelf', summary: 'Save bar ingredients', parameters: [
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

    #[OAT\Post(path: '/bars/{id}/ingredients/batch-delete', tags: ['Bars: Shelf'], operationId: 'batchDeleteBarShelfIngredients', description: 'Delete multiple ingredients from bar shelf', summary: 'Delete bar ingredients', parameters: [
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

    #[OAT\Get(path: '/bars/{id}/cocktails', tags: ['Bars: Shelf'], operationId: 'listBarShelfCocktails', summary: 'List bar shelf cocktails', description: 'Cocktails that the bar can make with ingredients on their shelf', parameters: [
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

    private function ownedBarMembership(Request $request, int $id): BarMembership
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        if ($barMembership === null) {
            abort(404);
        }

        return $barMembership;
    }

    private function ownedMemberInventory(Request $request, int $memberId, int $inventoryId): ModelMemberInventory
    {
        return $this->ownedBarMembership($request, $memberId)
            ->memberInventories()
            ->findOrFail($inventoryId);
    }

    private function legacyMemberInventory(BarMembership $barMembership): ?ModelMemberInventory
    {
        return $barMembership->memberInventories()
            ->with('inventoryIngredients')
            ->orderByRaw("CASE WHEN name = ? THEN 0 ELSE 1 END", [self::LEGACY_MEMBER_INVENTORY_NAME])
            ->orderBy('id')
            ->first();
    }

    /**
     * @return int[]
     */
    private function memberInventoryIngredientIds(ModelMemberInventory $memberInventory): array
    {
        return $memberInventory->inventoryIngredients->pluck('ingredient_id')->all();
    }

}
