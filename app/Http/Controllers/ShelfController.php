<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Http\Requests\IngredientsBatchRequest;
use Kami\Cocktail\Http\Resources\UserIngredientResource;

class ShelfController extends Controller
{
    #[OAT\Get(path: '/shelf/ingredients', tags: ['Shelf'], summary: 'Show a list of ingredients on the shelf', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\UserIngredient::class),
    ])]
    public function ingredients(Request $request): JsonResource
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $barMembership->load('userIngredients.ingredient');
        $userIngredients = $barMembership
            ->userIngredients
            ->sortBy('ingredient.name');

        return UserIngredientResource::collection($userIngredients);
    }

    #[OAT\Get(path: '/shelf/cocktails', tags: ['Shelf'], summary: 'Show a list of cocktails on the shelf', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new OAT\Parameter(name: 'limit', in: 'query', description: 'Limit the number of results', schema: new OAT\Schema(type: 'integer')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\JsonContent(properties: [new OAT\Property(property: 'data', type: 'array', description: 'List of cocktail ids that are on the shelf', items: new OAT\Items(type: 'integer'))]),
    ])]
    public function cocktails(CocktailRepository $cocktailRepo, Request $request): JsonResponse
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);
        $limit = $request->has('limit') ? (int) $request->get('limit') : null;

        $cocktailIds = $cocktailRepo->getCocktailsByIngredients(
            $barMembership->userIngredients->pluck('ingredient_id')->toArray(),
            $limit,
            $barMembership->use_parent_as_substitute,
        );

        return response()->json([
            'data' => $cocktailIds
        ]);
    }

    #[OAT\Get(path: '/shelf/cocktail-favorites', tags: ['Shelf'], summary: 'Show a list of cocktails on the shelf that the user has favorited', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new OAT\JsonContent(properties: [new OAT\Property(property: 'data', type: 'array', description: 'List of cocktail ids', items: new OAT\Items(type: 'integer'))]),
    ])]
    public function favorites(Request $request): JsonResponse
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        $cocktailIds = CocktailFavorite::where('bar_membership_id', $barMembership->id)->pluck('cocktail_id');

        return response()->json([
            'data' => $cocktailIds
        ]);
    }

    #[OAT\Post(path: '/shelf/ingredients/batch-store', tags: ['Shelf'], summary: 'Batch store ingredients', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'ingredient_ids', type: 'array', items: new OAT\Items(type: 'integer')),
            ]),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\UserIngredient::class),
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

        // Let's remove ingredients from shopping list since they are on our shelf now
        UserShoppingList::whereIn('ingredient_id', $ingredients)->delete();

        $models = [];
        foreach ($ingredients as $dbIngredientId) {
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $dbIngredientId;
            $models[] = $userIngredient;
        }

        $shelfIngredients = $barMembership->userIngredients()->saveMany($models);

        return UserIngredientResource::collection($shelfIngredients);
    }

    #[OAT\Post(path: '/shelf/ingredients/batch-delete', tags: ['Shelf'], summary: 'Delete multiple ingredients from the shelf', parameters: [
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
    public function batchDelete(Request $request): Response
    {
        $barMembership = $request->user()->getBarMembership(bar()->id);

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $request->post('ingredient_ids'))
            ->pluck('id');

        try {
            $barMembership->userIngredients()->whereIn('ingredient_id', $ingredients)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return new Response(null, 204);
    }
}
