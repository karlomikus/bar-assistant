<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Application\Bar\ShoppingListService;
use Kami\Cocktail\Http\Requests\IngredientsBatchRequest;
use Kami\Cocktail\Http\Resources\UserShoppingListResource;
use BarAssistant\Application\Bar\DTO\MemberShoppingListChangeRequest;
use BarAssistant\Application\Bar\DTO\MemberShoppingListRemoveIngredientRequest;

class ShoppingListController extends Controller
{
    #[OAT\Get(path: '/members/{id}/shopping-list', tags: ['Member shopping list'], operationId: 'listShoppingListIngredients', description: 'List all ingredients on a shopping list', summary: 'Show shopping list', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(UserShoppingListResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(Request $request, int $id): JsonResource
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        return UserShoppingListResource::collection(
            $user->getBarMembership(bar()->id)->shoppingListIngredients->load('ingredient')->sortBy('ingredient.name')
        );
    }

    #[OAT\Post(path: '/members/{id}/shopping-list/batch-store', tags: ['Member shopping list'], operationId: 'batchStoreShoppingListIngredients', description: 'Add multiple ingredients to a shopping list', summary: 'Add ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\ShoppingListRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function batchStore(IngredientsBatchRequest $request, ShoppingListService $shoppingListService, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        if ($barMembership === null) {
            abort(403);
        }

        $ingredients = $request->input('ingredients', []);

        Validator::make($ingredients, [
            '*.id' => [new ResourceBelongsToBar($barMembership->bar_id, 'ingredients')],
        ])->validate();

        $ingredientQuantityPairs = [];
        foreach ($ingredients as $input) {
            $ingredientQuantityPairs[$input['id']] = $input['quantity'] ?? 1;
        }

        $shoppingListService->addIngredientsToMemberShoppingList(new MemberShoppingListChangeRequest(
            memberId: $barMembership->id,
            ingredientQuantities: $ingredientQuantityPairs,
        ));

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/members/{id}/shopping-list/batch-delete', tags: ['Member shopping list'], operationId: 'batchDeleteShoppingListIngredients', description: 'Remove multiple ingredients from shopping list', summary: 'Delete ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'ingredients', type: 'array', items: new OAT\Items(type: 'object', properties: [
                    new OAT\Property(property: 'id', type: 'integer'),
                ])),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function batchDelete(IngredientsBatchRequest $request, ShoppingListService $shoppingListService, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        if ($barMembership === null) {
            abort(403);
        }

        $shoppingListService->removeIngredientsFromMemberShoppingList(new MemberShoppingListRemoveIngredientRequest(
            memberId: $barMembership->id,
            ingredientIds: $request->collect('ingredients')->pluck('id')->toArray()
        ));

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/members/{id}/shopping-list/share', tags: ['Member shopping list'], operationId: 'shareShoppingList', description: 'Get a shopping list in a specific format', summary: 'Share shopping list', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'type', in: 'query', description: 'Type of share. Available types: `markdown`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new OAT\JsonContent(required: ['data'], properties: [
            new OAT\Property(property: 'data', type: 'object', required: ['type', 'content'], properties: [
                new OAT\Property(property: 'type', type: 'string', example: 'markdown'),
                new OAT\Property(property: 'content', type: 'string', example: '<content in requested format>'),
            ]),
        ]),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function share(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        $type = $request->get('type', 'markdown');

        $shoppingListIngredients = $barMembership
            ->shoppingListIngredients
            ->load('ingredient');

        if ($type === 'markdown' || $type === 'md') {
            return response()->json([
                'data' => [
                    'type' => $type,
                    'content' => view('md_shopping_list_template', compact('shoppingListIngredients'))->render(),
                ]
            ]);
        }

        abort(400, 'Requested type "' . $type . '" not supported');
    }
}
