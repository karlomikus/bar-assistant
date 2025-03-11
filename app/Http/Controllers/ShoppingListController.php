<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientsBatchRequest;
use Kami\Cocktail\Http\Resources\UserShoppingListResource;

class ShoppingListController extends Controller
{
    #[OAT\Get(path: '/users/{id}/shopping-list', tags: ['Users: Shopping list'], operationId: 'listShoppingListIngredients', description: 'List all ingredients on a shopping list', summary: 'Show shopping list', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\ShoppingList::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(Request $request, int $id): JsonResource
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        return UserShoppingListResource::collection(
            $user->getBarMembership(bar()->id)->shoppingListIngredients->load('ingredient')->sortBy('ingredient.name')
        );
    }

    #[OAT\Post(path: '/users/{id}/shopping-list/batch-store', tags: ['Users: Shopping list'], operationId: 'batchStoreShoppingListIngredients', description: 'Add multiple ingredients to a shopping list', summary: 'Add ingredients', parameters: [
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
    public function batchStore(IngredientsBatchRequest $request, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $requestIngredients = $request->collect('ingredients');
        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $requestIngredients->pluck('id'))
            ->pluck('id');

        $existingShoppingListIngredients = UserShoppingList::where('bar_membership_id', $barMembership->id)
            ->whereIn('ingredient_id', $ingredients)
            ->get()
            ->keyBy('ingredient_id');

        $models = [];
        foreach ($ingredients as $ingId) {
            if ($usl = $existingShoppingListIngredients[$ingId] ?? null) {
                $usl->quantity = $requestIngredients->where('id', $ingId)->first()['quantity'] ?? 1;
                $usl->save();
            } else {
                $usl = new UserShoppingList();
                $usl->ingredient_id = $ingId;
                $usl->bar_membership_id = $barMembership->id;
                $usl->quantity = $requestIngredients->where('id', $ingId)->first()['quantity'] ?? 1;
                try {
                    $models[] = $barMembership->shoppingListIngredients()->save($usl);
                } catch (Throwable) {
                }
            }
        }

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/users/{id}/shopping-list/batch-delete', tags: ['Users: Shopping list'], operationId: 'batchDeleteShoppingListIngredients', description: 'Remove multiple ingredients from shopping list', summary: 'Delete ingredients', parameters: [
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
    public function batchDelete(IngredientsBatchRequest $request, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $requestIngredients = $request->collect('ingredients');
        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $requestIngredients->pluck('id'))
            ->pluck('id');

        try {
            $barMembership->shoppingListIngredients()->whereIn('ingredient_id', $ingredients)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/users/{id}/shopping-list/share', tags: ['Users: Shopping list'], operationId: 'shareShoppingList', description: 'Get a shopping list in a specific format', summary: 'Share shopping list', parameters: [
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
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
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
