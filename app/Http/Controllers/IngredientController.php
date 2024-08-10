<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Http\Requests\IngredientRequest;
use Kami\Cocktail\Repository\IngredientRepository;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Kami\Cocktail\Http\Filters\IngredientQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\Http\Resources\CocktailBasicResource;
use Kami\Cocktail\DTO\Ingredient\Ingredient as IngredientDTO;

class IngredientController extends Controller
{
    #[OAT\Get(path: '/ingredients', tags: ['Ingredients'], summary: 'Show a list of ingredients', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'integer'),
            new OAT\Property(property: 'name', type: 'string'),
            new OAT\Property(property: 'name_exact', type: 'string'),
            new OAT\Property(property: 'category_id', type: 'integer'),
            new OAT\Property(property: 'origin', type: 'string'),
            new OAT\Property(property: 'created_user_id', type: 'integer'),
            new OAT\Property(property: 'on_shopping_list', type: 'boolean'),
            new OAT\Property(property: 'on_shelf', type: 'boolean'),
            new OAT\Property(property: 'strength_min', type: 'float'),
            new OAT\Property(property: 'strength_max', type: 'float'),
            new OAT\Property(property: 'main_ingredients', type: 'string'),
            new OAT\Property(property: 'complex', type: 'boolean'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`, `strength`, `total_cocktails`.', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'includes', in: 'query', description: 'Include additional relationships. Available relations: `parentIngredient`, `varieties`, `cocktails`, `cocktailIngredientSubstitutes`, `prices`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\PaginateData(BAO\Schemas\Ingredient::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(IngredientRepository $ingredientQuery, Request $request): JsonResource
    {
        try {
            /** @var \Illuminate\Pagination\LengthAwarePaginator<Ingredient> */
            $ingredients = (new IngredientQueryFilter($ingredientQuery))->paginate($request->get('per_page', 50));
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        return IngredientResource::collection($ingredients->withQueryString());
    }

    #[OAT\Get(path: '/ingredients/{id}', tags: ['Ingredients'], summary: 'Show an ingredient', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Ingredient::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, string $id): JsonResource
    {
        $ingredient = Ingredient::with('cocktails', 'images', 'varieties', 'parentIngredient', 'createdUser', 'updatedUser', 'ingredientParts.ingredient', 'prices.priceCategory')
            ->withCount('cocktails')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        return new IngredientResource($ingredient);
    }

    #[OAT\Post(path: '/ingredients', tags: ['Ingredients'], summary: 'Create an ingredient', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Ingredient::class),
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

    #[OAT\Put(path: '/ingredients/{id}', tags: ['Ingredients'], summary: 'Update an ingredient', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Ingredient::class),
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

    #[OAT\Delete(path: '/ingredients/{id}', tags: ['Ingredients'], summary: 'Delete an ingredient', parameters: [
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

    #[OAT\Get(path: '/ingredients/{id}/extra', tags: ['Ingredients'], summary: 'Show how many extra cocktails you can make if you add this ingredient to your shelf', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientBasic::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function extra(Request $request, CocktailRepository $cocktailRepo, int $id): JsonResponse
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        $currentShelfIngredients = $request->user()->getShelfIngredients($ingredient->bar_id)->pluck('ingredient_id');
        $currentShelfCocktails = $cocktailRepo->getCocktailsByIngredients($currentShelfIngredients->toArray())->values();
        $extraShelfCocktails = $cocktailRepo->getCocktailsByIngredients($currentShelfIngredients->push($ingredient->id)->toArray())->values();

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

    #[OAT\Get(path: '/ingredients/{id}/cocktails', tags: ['Ingredients'], summary: 'Show a list of cocktails that use this ingredient', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\PaginateData(BAO\Schemas\CocktailBasic::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function cocktails(Request $request, string $id): JsonResource
    {
        $ingredient = Ingredient::with('cocktails')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        $cocktails = $ingredient->cocktails->toQuery()->orderBy('name')->paginate($request->get('per_page', 100));

        return CocktailBasicResource::collection($cocktails);
    }
}
