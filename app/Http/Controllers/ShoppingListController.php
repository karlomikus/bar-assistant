<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientsBatchRequest;
use Kami\Cocktail\Http\Resources\UserShoppingListResource;

class ShoppingListController extends Controller
{
    #[OAT\Get(path: '/shopping-list', tags: ['Shopping list'], summary: 'Show shopping list', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ], security: [])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\ShoppingList::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(Request $request): JsonResource
    {
        return UserShoppingListResource::collection(
            $request->user()->getBarMembership(bar()->id)->shoppingListIngredients->load('ingredient')
        );
    }

    #[OAT\Post(path: '/shopping-list/batch-store', tags: ['Shopping list'], summary: 'Batch add ingredients to shopping list', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'ingredient_ids', type: 'array', items: new OAT\Items(type: 'integer')),
            ]),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\ShoppingList::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function batchStore(IngredientsBatchRequest $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $request->post('ingredient_ids'))
            ->pluck('id');

        $models = [];
        foreach ($ingredients as $ingId) {
            $usl = new UserShoppingList();
            $usl->ingredient_id = $ingId;
            $usl->bar_membership_id = $barMembership->id;
            try {
                $models[] = $barMembership->shoppingListIngredients()->save($usl);
            } catch (Throwable) {
            }
        }

        return UserShoppingListResource::collection($models);
    }

    #[OAT\Post(path: '/shopping-list/batch-delete', tags: ['Shopping list'], summary: 'Batch delete ingredients from shopping list', parameters: [
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
    public function batchDelete(IngredientsBatchRequest $request): Response
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

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

    #[OAT\Get(path: '/shopping-list/share', tags: ['Shopping list'], summary: 'Share shopping list', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ], security: [])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\MediaType(mediaType: 'text/markdown', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function share(Request $request): Response
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
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
