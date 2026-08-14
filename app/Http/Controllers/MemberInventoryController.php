<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use BarAssistant\Application\Bar\MemberInventoryService;
use Kami\Cocktail\Http\Requests\ShelfIngredientsRequest;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;
use Kami\Cocktail\Http\Resources\MemberInventoryResource;
use BarAssistant\Application\Bar\DTO\DeleteInventoryRequest;
use BarAssistant\Application\Bar\DTO\UpdateInventoryNameRequest;
use Kami\Cocktail\Models\MemberInventory as ModelMemberInventory;
use BarAssistant\Application\Bar\DTO\CreateMemberInventoryRequest;
use BarAssistant\Application\Bar\DTO\MemberInventoryStockChangeRequest;

class MemberInventoryController extends Controller
{
    #[OAT\Get(path: '/members/{id}/inventories', tags: ['Member inventory'], operationId: 'listMemberInventories', summary: 'List member inventories', description: 'List all inventories owned by the current member', parameters: [
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

    #[OAT\Post(path: '/members/{id}/inventories', tags: ['Member inventory'], operationId: 'createMemberInventory', summary: 'Create member inventory', description: 'Create a new inventory for the current member', parameters: [
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

    #[OAT\Patch(path: '/members/{id}/inventories/{inventoryId}', tags: ['Member inventory'], operationId: 'updateMemberInventory', summary: 'Update member inventory', description: 'Rename a member inventory', parameters: [
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

    #[OAT\Delete(path: '/members/{id}/inventories/{inventoryId}', tags: ['Member inventory'], operationId: 'deleteMemberInventory', summary: 'Delete member inventory', description: 'Delete a member inventory', parameters: [
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

    #[OAT\Get(path: '/members/{id}/inventories/{inventoryId}/ingredients', tags: ['Member inventory'], operationId: 'listMemberInventoryIngredients', summary: 'List member inventory ingredients', description: 'List ingredients stored in a member inventory', parameters: [
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

    #[OAT\Post(path: '/members/{id}/inventories/{inventoryId}/ingredients/batch-store', tags: ['Member inventory'], operationId: 'batchStoreMemberInventoryIngredients', summary: 'Save member inventory ingredients', description: 'Save multiple ingredients to a member inventory', parameters: [
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

    #[OAT\Post(path: '/members/{id}/inventories/{inventoryId}/ingredients/batch-delete', tags: ['Member inventory'], operationId: 'batchDeleteMemberInventoryIngredients', summary: 'Delete member inventory ingredients', description: 'Delete multiple ingredients from a member inventory', parameters: [
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

    #[OAT\Get(path: '/members/{id}/inventories/{inventoryId}/cocktails', tags: ['Member inventory'], operationId: 'listMemberInventoryCocktails', summary: 'List member inventory cocktails', description: 'List cocktails makeable from a member inventory', parameters: [
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

    #[OAT\Get(path: '/members/{id}/inventories/{inventoryId}/recommend', tags: ['Member inventory'], operationId: 'recommendMemberInventoryIngredients', summary: 'Recommend member inventory ingredients', description: 'Recommend ingredients for a member inventory', parameters: [
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

        $possibleIngredients = $ingredientRepo->getIngredientsOrderedByUnlockedCocktails(
            bar()->id,
            $this->memberInventoryIngredientIds($memberInventory),
        );

        return response()->json(['data' => $possibleIngredients]);
    }

    #[OAT\Get(path: '/members/{id}/cocktail-favorites', tags: ['Member inventory'], operationId: 'listUserFavoriteCocktails', description: 'Show a list of cocktails user has favorited', summary: 'List favorites', parameters: [
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

    /**
     * @return int[]
     */
    private function memberInventoryIngredientIds(ModelMemberInventory $memberInventory): array
    {
        return $memberInventory->inventoryIngredients->pluck('ingredient_id')->all();
    }
}
