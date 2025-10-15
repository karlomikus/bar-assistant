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
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use Kami\Cocktail\Http\Requests\ShelfIngredientsRequest;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;

class ShelfController extends Controller
{
    #[OAT\Get(path: '/users/{id}/ingredients', tags: ['Users: Shelf'], operationId: 'listUserIngredients', summary: 'List user ingredients', description: 'Ingredients that user saved to their shelf', parameters: [
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
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);
        $barMembership->load('userIngredients.ingredient');
        $userIngredientIds = $barMembership
            ->userIngredients
            ->pluck('ingredient_id');

        $ingredients = Ingredient::whereIn('id', $userIngredientIds)->orderBy('name')->paginate($request->get('per_page', 100));

        return IngredientBasicResource::collection($ingredients->withQueryString());
    }

    #[OAT\Get(path: '/users/{id}/cocktails', tags: ['Users: Shelf'], operationId: 'listUserShelfCocktails', summary: 'List shelf cocktails', description: 'Cocktails that the user can make with ingredients on their shelf', parameters: [
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
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        $cocktailIds = $cocktailRepo->getCocktailsByIngredients(
            $barMembership->userIngredients->pluck('ingredient_id')->toArray(),
            bar()->id,
            null,
        );

        $cocktails = Cocktail::whereIn('id', $cocktailIds)->with('ingredients.ingredient')->paginate($request->get('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/users/{id}/cocktails/favorites', tags: ['Users: Shelf'], operationId: 'listUserFavoriteCocktails', description: 'Show a list of cocktails user has favorited', summary: 'List favorites', parameters: [
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

        $cocktails = Cocktail::whereIn('id', $cocktailIds)->with('ingredients.ingredient')->paginate($request->get('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Post(path: '/users/{id}/ingredients/batch-store', tags: ['Users: Shelf'], operationId: 'batchStoreUserIngredients', description: 'Save multiple ingredients to user shelf', summary: 'Save user ingredients', parameters: [
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

    #[OAT\Post(path: '/users/{id}/ingredients/batch-delete', tags: ['Users: Shelf'], operationId: 'batchDeleteUserIngredients', description: 'Delete multiple ingredients from user shelf', summary: 'Delete user ingredients', parameters: [
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

    #[OAT\Get(path: '/users/{id}/ingredients/recommend', tags: ['Users: Shelf'], operationId: 'recommendIngredients', description: 'Shows a list of ingredients that will increase total shelf cocktails when added to user shef', summary: 'Recommend user ingredients', parameters: [
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
        $user = User::findOrFail($id);
        if ($request->user()->id !== $user->id && $request->user()->cannot('show', $user)) {
            abort(403);
        }

        $barMembership = $user->getBarMembership(bar()->id);

        if (!$barMembership) {
            abort(404);
        }

        $possibleIngredients = $ingredientRepo->getIngredientsForPossibleCocktails(bar()->id, $barMembership->userIngredients->pluck('ingredient_id')->toArray());

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

        $ingredients = Ingredient::whereIn('id', $ingredientIds)->orderBy('name')->paginate($request->get('per_page', 100));

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
    public function batchStoreBarIngredients(ShelfIngredientsRequest $request, int $id): Response
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('manageShelf', $bar)) {
            abort(403);
        }
        $bar->load('shelfIngredients');

        $existingBarShelfIngredients = $bar->shelfIngredients->pluck('ingredient_id');
        $ingredients = DB::table('ingredients')
            ->select('id')
            ->where('bar_id', $bar->id)
            ->whereIn('id', $request->post('ingredients'))
            ->pluck('id');

        $models = [];
        foreach ($ingredients as $dbIngredientId) {
            if ($existingBarShelfIngredients->contains($dbIngredientId)) {
                continue;
            }

            $userIngredient = new BarIngredient();
            $userIngredient->ingredient_id = $dbIngredientId;
            $models[] = $userIngredient;
        }

        $bar->shelfIngredients()->saveMany($models);

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

        $cocktails = Cocktail::whereIn('id', $cocktailIds)->with('ingredients.ingredient')->paginate($request->get('per_page', 100));

        return CocktailBasicResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/bars/{id}/ingredients/recommend', tags: ['Bars: Shelf'], operationId: 'recommendBarIngredients', description: 'Shows a list of ingredients that will increase total bar shelf cocktails when added to bar shef', summary: 'Recommend bar ingredients', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientRecommend::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function recommendBarIngredients(Request $request, IngredientService $ingredientRepo, int $id): \Illuminate\Http\JsonResponse
    {
        $bar = Bar::findOrFail($id);
        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $possibleIngredients = $ingredientRepo->getIngredientsForPossibleCocktails($bar->id, $bar->shelfIngredients->pluck('ingredient_id')->toArray());

        return response()->json(['data' => $possibleIngredients]);
    }
}
