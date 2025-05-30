<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\DB;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientRequest;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Kami\Cocktail\Http\Filters\IngredientQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use Kami\Cocktail\Http\Resources\IngredientTreeResource;
use Kami\Cocktail\Http\Resources\IngredientBasicResource;
use Kami\Cocktail\OpenAPI\Schemas\IngredientRequest as IngredientDTO;

class IngredientController extends Controller
{
    #[OAT\Get(path: '/ingredients', tags: ['Ingredients'], operationId: 'listIngredients', description: 'Show a list of all ingredients in a bar', summary: 'List ingredients', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'integer', description: 'Filter by ingredient id'),
            new OAT\Property(property: 'name', type: 'string', description: 'Filter by ingredient name (fuzzy search)'),
            new OAT\Property(property: 'name_exact', type: 'string', description: 'Filter by ingredient name (exact match)'),
            new OAT\Property(property: 'origin', type: 'string', description: 'Filter by ingredient origin'),
            new OAT\Property(property: 'created_user_id', type: 'integer', description: 'Filter by user id who created the ingredient'),
            new OAT\Property(property: 'on_shopping_list', type: 'boolean', description: 'Show only ingredients that are on the shopping list'),
            new OAT\Property(property: 'on_shelf', type: 'boolean', description: 'Show only ingredients that are on the shelf'),
            new OAT\Property(property: 'bar_shelf', type: 'boolean', description: 'Show only ingredients that are on the bar shelf'),
            new OAT\Property(property: 'strength_min', type: 'float', description: 'Show only ingredients with strength greater than or equal to given value'),
            new OAT\Property(property: 'strength_max', type: 'float', description: 'Show only ingredients with strength less than or equal to given value'),
            new OAT\Property(property: 'main_ingredients', type: 'string', description: 'Show only ingredients that are used as main ingredients in cocktails'),
            new OAT\Property(property: 'complex', type: 'boolean', description: 'Show only ingredients that can be made with other ingredients'),
            new OAT\Property(property: 'parent_ingredient_id', type: 'integer', description: 'Show only direct children of given ingredient. Use null as value to get ingredients without parent ingredient'),
            new OAT\Property(property: 'descendants_of', type: 'integer', description: 'Show all descendants of given ingredient'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`, `strength`, `total_cocktails`.', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'include', in: 'query', description: 'Include additional relationships. Available relations: `parentIngredient`, `varieties`, `prices`, `ingredientParts`, `descendants`, `ancestors`, `images`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(IngredientResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(IngredientService $ingredientQuery, Request $request): JsonResource
    {
        try {
            $ingredients = (new IngredientQueryFilter($ingredientQuery))->paginate($request->get('per_page', 50));
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        return IngredientResource::collection($ingredients->withQueryString());
    }

    #[OAT\Get(path: '/ingredients/{id}', tags: ['Ingredients'], operationId: 'showIngredient', description: 'Show a specific ingredient', summary: 'Show ingredient', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(IngredientResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, string $idOrSlug): JsonResource
    {
        $ingredient = Ingredient::with(
            'cocktails',
            'images',
            'parentIngredient',
            'createdUser',
            'updatedUser',
            'ingredientParts.ingredient',
            'prices.priceCategory',
            'cocktailIngredientSubstitutes.cocktailIngredient.ingredient',
            'descendants',
            'ancestors'
        )
            ->withCount('cocktails')
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        return new IngredientResource($ingredient);
    }

    #[OAT\Post(path: '/ingredients', tags: ['Ingredients'], operationId: 'saveIngredient', description: 'Create a new ingredient', summary: 'Create ingredient', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(IngredientResource::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(IngredientService $ingredientService, IngredientRequest $request): JsonResponse
    {
        Validator::make($request->all(), [
            'complex_ingredient_part_ids' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
            'prices.*.price_category_id' => [new ResourceBelongsToBar(bar()->id, 'price_categories')],
        ])->validate();

        if ($request->user()->cannot('create', Ingredient::class)) {
            abort(403);
        }

        $ingredient = $ingredientService->createIngredient(
            IngredientDTO::fromIlluminateRequest($request, bar()->id)
        );

        return (new IngredientResource($ingredient))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('ingredients.show', $ingredient->id));
    }

    #[OAT\Put(path: '/ingredients/{id}', tags: ['Ingredients'], operationId: 'updateIngredient', description: 'Update a specific ingredient', summary: 'Update ingredient', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(IngredientResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(IngredientService $ingredientService, IngredientRequest $request, int $id): JsonResource
    {
        $ingredient = Ingredient::findOrFail($id);

        Validator::make($request->all(), [
            'complex_ingredient_part_ids' => [new ResourceBelongsToBar($ingredient->bar_id, 'ingredients')],
            'prices.*.price_category_id' => [new ResourceBelongsToBar($ingredient->bar_id, 'price_categories')],
        ])->validate();

        if ($request->user()->cannot('edit', $ingredient)) {
            abort(403);
        }

        $ingredient = $ingredientService->updateIngredient(
            $id,
            IngredientDTO::fromIlluminateRequest($request, $ingredient->bar_id)
        );

        return new IngredientResource($ingredient);
    }

    #[OAT\Delete(path: '/ingredients/{id}', tags: ['Ingredients'], operationId: 'deleteIngredient', description: 'Delete a specific ingredient', summary: 'Delete ingredient', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('delete', $ingredient)) {
            abort(403);
        }

        $ingredient->delete();

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/ingredients/{id}/extra', tags: ['Ingredients'], operationId: 'extraIngredients', description: 'Show a list of extra cocktails you can make if you add given ingredient to your shelf', summary: 'Extra cocktails', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CocktailBasicResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function extra(Request $request, CocktailService $cocktailRepo, string $idOrSlug): JsonResponse
    {
        $ingredient = Ingredient::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        $currentShelfIngredients = $request->user()->getShelfIngredients($ingredient->bar_id)->pluck('ingredient_id');
        $currentShelfCocktails = $cocktailRepo->getCocktailsByIngredients($currentShelfIngredients->toArray(), $ingredient->bar_id)->values();
        $extraShelfCocktails = $cocktailRepo->getCocktailsByIngredients($currentShelfIngredients->push($ingredient->id)->toArray(), $ingredient->bar_id)->values();

        if ($currentShelfCocktails->count() === $extraShelfCocktails->count()) {
            return response()->json(['data' => []]);
        }

        $extraCocktails = Cocktail::whereIn('id', $extraShelfCocktails->diff($currentShelfCocktails)->values())->where('bar_id', '=', $ingredient->bar_id)->get();

        return response()->json([
            'data' => $extraCocktails->map(function (Cocktail $cocktail) {
                return [
                    'id' => $cocktail->id,
                    'slug' => $cocktail->slug,
                    'name' => $cocktail->name,
                ];
            })
        ]);
    }

    #[OAT\Get(path: '/ingredients/{id}/cocktails', tags: ['Ingredients'], operationId: 'ingredientCocktails', description: 'List all cocktails that use this ingredient', summary: 'List cocktails', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(CocktailBasicResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function cocktails(Request $request, string $idOrSlug): JsonResource
    {
        $ingredient = Ingredient::with('cocktails')
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        $cocktailIds = DB::table('cocktail_ingredients')
            ->select('cocktail_id')
            ->where('ingredient_id', $idOrSlug) // Matches cocktails that use the ingredient directly
            ->union(
                DB::table('cocktail_ingredients')
                    ->select('cocktail_id')
                    ->join('cocktail_ingredient_substitutes', 'cocktail_ingredient_substitutes.cocktail_ingredient_id', '=', 'cocktail_ingredients.id')
                    ->where('cocktail_ingredient_substitutes.ingredient_id', $idOrSlug) // Matches cocktails that use the ingredient as a substitute
            )
            ->pluck('cocktail_id');

        $cocktails = Cocktail::whereIn('id', $cocktailIds)->with('ingredients.ingredient')->orderBy('name')->paginate($request->get('per_page', 100));

        return CocktailBasicResource::collection($cocktails);
    }

    #[OAT\Get(path: '/ingredients/{id}/substitutes', tags: ['Ingredients'], operationId: 'ingredientSubstitutes', summary: 'List ingredient substitutes', description: 'Show a list of ingredients that are used as a substitute for this ingredient in cocktail recipes.', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(IngredientBasicResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function substitutes(Request $request, string $idOrSlug): JsonResource
    {
        $ingredient = Ingredient::with('cocktails')
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        $ids = DB::table('cocktail_ingredients')
            ->select('cocktail_ingredient_substitutes.ingredient_id')
            ->where('cocktail_ingredients.ingredient_id', $idOrSlug)
            ->join('cocktail_ingredient_substitutes', 'cocktail_ingredient_substitutes.cocktail_ingredient_id', '=', 'cocktail_ingredients.id')
            ->pluck('cocktail_ingredient_substitutes.ingredient_id');

        $cocktails = Ingredient::whereIn('id', $ids)->orderBy('name')->paginate($request->get('per_page', 100));

        return IngredientBasicResource::collection($cocktails);
    }

    #[OAT\Get(path: '/ingredients/{id}/tree', tags: ['Ingredients'], operationId: 'showIngredientTree', description: 'Show a ingredient hierarchy as a tree', summary: 'Show tree', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(IngredientTreeResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function tree(Request $request, string $idOrSlug): IngredientTreeResource
    {
        $ingredient = Ingredient::with('allChildren')
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        return new IngredientTreeResource($ingredient);
    }
}
