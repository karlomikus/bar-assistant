<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientsBatchRequest;
use Kami\Cocktail\Http\Resources\UserShoppingListResource;

class ShoppingListController extends Controller
{
    #[OAT\Get(path: '/users/{id}/shopping-list', tags: ['Users: Shopping list'], summary: 'Show shopping list', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\ShoppingList::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(Request $request, int $id): JsonResource
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id || $request->user()->cannot('show', $user)) {
            abort(403);
        }

        return UserShoppingListResource::collection(
            $user->getBarMembership(bar()->id)->shoppingListIngredients->load('ingredient')
        );
    }

    #[OAT\Post(path: '/users/{id}/shopping-list/batch-store', tags: ['Users: Shopping list'], summary: 'Batch add ingredients to shopping list', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
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
        if ($request->user()->id !== $user->id || $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $requestIngredients = collect($request->post('ingredients'));
        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $requestIngredients->pluck('id'))
            ->pluck('id');

        $models = [];
        foreach ($ingredients as $ingId) {
            if ($usl = UserShoppingList::where('bar_membership_id', $barMembership->id)->where('ingredient_id', $ingId)->first()) {
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

    #[OAT\Post(path: '/users/{id}/shopping-list/batch-delete', tags: ['Users: Shopping list'], summary: 'Batch delete ingredients from shopping list', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'ingredient_ids', type: 'array', items: new OAT\Items(type: 'integer')),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function batchDelete(IngredientsBatchRequest $request, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id || $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $request->post('ingredient_ids'))
            ->pluck('id');

        try {
            $barMembership->shoppingListIngredients()->whereIn('ingredient_id', $ingredients)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/users/{id}/shopping-list/share', tags: ['Users: Shopping list'], summary: 'Share shopping list', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
        new OAT\Parameter(name: 'type', in: 'query', description: 'Type of share. Available types: `markdown`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\MediaType(mediaType: 'text/markdown', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function share(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id || $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        $type = $request->get('type', 'markdown');

        $shoppingListIngredients = $barMembership
            ->shoppingListIngredients
            ->load('ingredient.category')
            ->groupBy('ingredient.category.name');

        if ($type === 'markdown' || $type === 'md') {
            return new Response(
                view('md_shopping_list_template', compact('shoppingListIngredients'))->render(),
                200,
                ['Content-Type' => 'text/markdown']
            );
        }

        abort(400, 'Requested type "' . $type . '" not supported');
    }
}
