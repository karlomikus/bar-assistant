<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\BarIngredient;
use Kami\Cocktail\Models\UserIngredient;
use Kami\Cocktail\Models\CocktailFavorite;
use Kami\Cocktail\Models\UserShoppingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Repository\IngredientRepository;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use Kami\Cocktail\Http\Requests\ShelfIngredientsRequest;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;

class ShelfController extends Controller
{
    #[OAT\Get(path: '/users/{id}/ingredients', tags: ['Users: Shelf'], summary: 'Show a list of shelf ingredients', description: 'Ingredients that user saved to their shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\PaginateData(BAO\Schemas\IngredientBasic::class),
    ])]
    public function ingredients(Request $request, int $id): JsonResource
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        $barMembership->load('userIngredients.ingredient');
        $userIngredientIds = $barMembership
            ->userIngredients
            ->pluck('ingredient_id');

        /** @var \Illuminate\Pagination\LengthAwarePaginator<Ingredient> */
        $ingredients = Ingredient::whereIn('id', $userIngredientIds)->orderBy('name')->paginate($request->get('per_page', 100));

        return IngredientBasicResource::collection($ingredients->withQueryString());
    }

    #[OAT\Get(path: '/users/{id}/cocktails', tags: ['Users: Shelf'], summary: 'Show a list shelf cocktails', description: 'Cocktails that the user can make with ingredients on their shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\PaginateData(BAO\Schemas\CocktailBasic::class),
    ])]
    public function cocktails(CocktailRepository $cocktailRepo, Request $request, int $id): JsonResource
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $cocktailIds = $cocktailRepo->getCocktailsByIngredients(
            $barMembership->userIngredients->pluck('ingredient_id')->toArray(),
            null,
            $barMembership->use_parent_as_substitute,
        );

        /** @var \Illuminate\Pagination\LengthAwarePaginator<Cocktail> */
        $cocktails = Cocktail::whereIn('id', $cocktailIds)->paginate($request->get('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/users/{id}/cocktails/favorites', tags: ['Users: Shelf'], summary: 'Show a list of cocktails user has favorited', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\PaginateData(BAO\Schemas\CocktailBasic::class),
    ])]
    public function favorites(Request $request, int $id): JsonResource
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $cocktailIds = CocktailFavorite::where('bar_membership_id', $barMembership->id)->pluck('cocktail_id');

        /** @var \Illuminate\Pagination\LengthAwarePaginator<Cocktail> */
        $cocktails = Cocktail::whereIn('id', $cocktailIds)->paginate($request->get('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Post(path: '/users/{id}/ingredients/batch-store', tags: ['Users: Shelf'], summary: 'Batch store ingredients to the shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
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
    public function batchStore(ShelfIngredientsRequest $request, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        $existingBarIngredients = $barMembership->userIngredients->pluck('ingredient_id');

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $request->post('ingredients'))
            ->pluck('id');

        // Let's remove ingredients from shopping list since they are on our shelf now
        UserShoppingList::whereIn('ingredient_id', $ingredients)->delete();

        $models = [];
        foreach ($ingredients as $dbIngredientId) {
            if ($existingBarIngredients->contains($dbIngredientId)) {
                continue;
            }
            $userIngredient = new UserIngredient();
            $userIngredient->ingredient_id = $dbIngredientId;
            $models[] = $userIngredient;
        }

        $barMembership->userIngredients()->saveMany($models);

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/users/{id}/ingredients/batch-delete', tags: ['Users: Shelf'], summary: 'Delete multiple ingredients from the shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
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
    public function batchDelete(ShelfIngredientsRequest $request, int $id): Response
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $barMembership->bar_id)
            ->whereIn('id', $request->post('ingredients'))
            ->pluck('id');

        try {
            $barMembership->userIngredients()->whereIn('ingredient_id', $ingredients)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/users/{id}/ingredients/recommend', tags: ['Users: Shelf'], summary: 'Recommend next ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientRecommend::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function recommend(Request $request, IngredientRepository $ingredientRepo, int $id): \Illuminate\Http\JsonResponse
    {
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        if (!$barMembership) {
            abort(404);
        }

        $possibleIngredients = $ingredientRepo->getIngredientsForPossibleCocktails(bar()->id, $barMembership->id);

        return response()->json(['data' => $possibleIngredients]);
    }

    #[OAT\Get(path: '/bars/{id}/ingredients', tags: ['Bar Shelf'], summary: 'Show a list of bar shelf ingredients', description: 'Ingredients that bar has in it\'s shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\PaginateData(BAO\Schemas\IngredientBasic::class),
    ])]
    public function barIngredients(Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $ingredientIds = $bar->shelfIngredients->pluck('ingredient_id');

        /** @var \Illuminate\Pagination\LengthAwarePaginator<Ingredient> */
        $ingredients = Ingredient::whereIn('id', $ingredientIds)->orderBy('name')->paginate($request->get('per_page', 100));

        return IngredientBasicResource::collection($ingredients->withQueryString());
    }

    #[OAT\Post(path: '/bars/{id}/ingredients/batch-store', tags: ['Bar Shelf'], summary: 'Batch store bar ingredients to bar shelf', parameters: [
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
    public function batchStoreBarIngredients(ShelfIngredientsRequest $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('manageShelf', $bar)) {
            abort(403);
        }

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $bar->id)
            ->whereIn('id', $request->post('ingredients'))
            ->pluck('id');

        $models = [];
        foreach ($ingredients as $dbIngredientId) {
            $userIngredient = new BarIngredient();
            $userIngredient->ingredient_id = $dbIngredientId;
            $models[] = $userIngredient;
        }

        $bar->shelfIngredients()->saveMany($models);

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/bars/{id}/ingredients/batch-delete', tags: ['Bar Shelf'], summary: 'Delete multiple ingredients from bar shelf', parameters: [
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
    public function batchDeleteBarIngredients(ShelfIngredientsRequest $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('manageShelf', $bar)) {
            abort(403);
        }

        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $bar->id)
            ->whereIn('id', $request->post('ingredients'))
            ->pluck('id');

        try {
            $bar->shelfIngredients()->whereIn('ingredient_id', $ingredients)->delete();
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return new Response(null, 204);
    }
}
