<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\CocktailPrice;
use Kami\Cocktail\Models\PriceCategory;
use Illuminate\Support\Facades\Validator;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\Services\Image\ImageService;
use Kami\Cocktail\OpenAPI\Schemas\ImageRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Filters\CocktailQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\Http\Resources\CocktailPriceResource;
use Kami\Cocktail\External\Model\Schema as SchemaDraft2;
use Kami\Cocktail\OpenAPI\Schemas\CocktailRequest as CocktailDTO;
use Kami\Cocktail\OpenAPI\Schemas\CocktailIngredientRequest as IngredientDTO;
use Kami\Cocktail\OpenAPI\Schemas\CocktailIngredientSubstituteRequest as SubstituteDTO;
use Kami\Cocktail\Repository\IngredientRepository;

class CocktailController extends Controller
{
    #[OAT\Get(path: '/cocktails', tags: ['Cocktails'], operationId: 'listCocktails', description: 'Show a list of all cocktails in a bar', summary: 'List cocktails', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'string'),
            new OAT\Property(property: 'name', type: 'string'),
            new OAT\Property(property: 'ingredient_name', type: 'string'),
            new OAT\Property(property: 'tag_id', type: 'string'),
            new OAT\Property(property: 'created_user_id', type: 'string'),
            new OAT\Property(property: 'glass_id', type: 'string'),
            new OAT\Property(property: 'cocktail_method_id', type: 'string'),
            new OAT\Property(property: 'collection_id', type: 'string'),
            new OAT\Property(property: 'favorites', type: 'boolean'),
            new OAT\Property(property: 'on_shelf', type: 'boolean'),
            new OAT\Property(property: 'bar_shelf', type: 'boolean'),
            new OAT\Property(property: 'user_shelves', type: 'string'),
            new OAT\Property(property: 'shelf_ingredients', type: 'string'),
            new OAT\Property(property: 'is_public', type: 'boolean'),
            new OAT\Property(property: 'user_rating_min', type: 'string'),
            new OAT\Property(property: 'user_rating_max', type: 'string'),
            new OAT\Property(property: 'average_rating_min', type: 'string'),
            new OAT\Property(property: 'average_rating_max', type: 'string'),
            new OAT\Property(property: 'abv_min', type: 'string'),
            new OAT\Property(property: 'abv_max', type: 'string'),
            new OAT\Property(property: 'main_ingredient_id', type: 'string'),
            new OAT\Property(property: 'total_ingredients', type: 'string'),
            new OAT\Property(property: 'missing_ingredients', type: 'string'),
            new OAT\Property(property: 'missing_bar_ingredients', type: 'string'),
            new OAT\Property(property: 'specific_ingredients', type: 'string'),
            new OAT\Property(property: 'ignore_ingredients', type: 'string'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`, `average_rating`, `user_rating`, `abv`, `total_ingredients`, `missing_ingredients`, `missing_bar_ingredients`, `favorited_at`, `random`.', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'include', in: 'query', description: 'Include additional relationships. Available relations: `glass`, `method`, `user`, `navigation`, `utensils`, `createdUser`, `updatedUser`, `images`, `tags`, `ingredients.ingredient`, `ratings`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(CocktailRepository $cocktailRepo, Request $request): JsonResource
    {
        try {
            $cocktails = new CocktailQueryFilter($cocktailRepo);
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator<Cocktail> */
        $cocktails = $cocktails->paginate($request->get('per_page', 25));

        return CocktailResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/cocktails/{id}', tags: ['Cocktails'], operationId: 'showCocktail', description: 'Show details of a specific cocktail', summary: 'Show cocktail', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(string $idOrSlug, Request $request, IngredientRepository $ingredientQuery): JsonResource
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->withRatings($request->user()->id)
            ->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $cocktail->load([
            'ingredients.ingredient.ingredientParts',
            'images',
            'tags',
            'glass',
            'ingredients.substitutes',
            'method',
            'createdUser',
            'updatedUser',
            'collections',
            'utensils',
            'ratings',
            'ingredients.ingredient.bar.shelfIngredients',
            'ingredients.ingredient.descendants',
        ]);

        return new CocktailResource($cocktail);
    }

    #[OAT\Post(path: '/cocktails', tags: ['Cocktails'], operationId: 'saveCocktail', description: 'Create a new cocktail', summary: 'Create cocktail', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CocktailRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\ValidationFailedResponse]
    public function store(CocktailService $cocktailService, CocktailRequest $request, IngredientRepository $ingredientQuery): JsonResponse
    {
        Validator::make($request->input('ingredients', []), [
            '*.ingredient_id' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
        ])->validate();

        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $cocktailDTO = CocktailDTO::fromIlluminateRequest($request);

        try {
            $cocktail = $cocktailService->createCocktail($cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load(['ingredients.ingredient.ingredientParts', 'images', 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils', 'ratings', 'ingredients.ingredient.bar.ingredients']);

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    #[OAT\Put(path: '/cocktails/{id}', tags: ['Cocktails'], operationId: 'updateCocktail', description: 'Update a specific cocktail', summary: 'Update cocktail', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CocktailRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function update(CocktailService $cocktailService, CocktailRequest $request, int $id, IngredientRepository $ingredientQuery): JsonResource
    {
        $cocktail = Cocktail::findOrFail($id);

        Validator::make($request->input('ingredients', []), [
            '*.ingredient_id' => [new ResourceBelongsToBar($cocktail->bar_id, 'ingredients')],
        ])->validate();

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $cocktailDTO = CocktailDTO::fromIlluminateRequest($request);

        try {
            $cocktail = $cocktailService->updateCocktail($id, $cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load(['ingredients.ingredient', 'images' => function ($query) {
            $query->orderBy('sort');
        }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils']);

        return new CocktailResource($cocktail);
    }

    #[OAT\Delete(path: '/cocktails/{id}', tags: ['Cocktails'], operationId: 'deleteCocktail', description: 'Delete a specific cocktail', summary: 'Delete cocktail', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('delete', $cocktail)) {
            abort(403);
        }

        $cocktail->delete();

        return new Response(null, 204);
    }

    #[OAT\Post(path: '/cocktails/{id}/toggle-favorite', tags: ['Cocktails'], operationId: 'toggleCocktailFavorite', description: 'Marks cocktail as users favorite. Can be called again to remove the favorite.', summary: 'Toggle favorite', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new OAT\JsonContent(properties: [new OAT\Property(property: 'data', type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'integer', example: 1),
            new OAT\Property(property: 'is_favorited', type: 'boolean', example: true),
        ])]),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function toggleFavorite(CocktailService $cocktailService, Request $request, int $id): JsonResponse
    {
        $userFavorite = $cocktailService->toggleFavorite($request->user(), $id);

        return response()->json([
            'data' => ['id' => $id, 'is_favorited' => $userFavorite !== null]
        ]);
    }

    #[OAT\Post(path: '/cocktails/{id}/public-link', tags: ['Cocktails'], operationId: 'createCocktailPublicLink', description: 'Create a public link that can be shared', summary: 'Create a public ID', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function makePublic(Request $request, string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('sharePublic', $cocktail)) {
            abort(403);
        }

        if ($cocktail->public_id) {
            return new CocktailResource($cocktail);
        }

        $cocktail = $cocktail->makePublic(now());

        return new CocktailResource($cocktail);
    }

    #[OAT\Delete(path: '/cocktails/{id}/public-link', tags: ['Cocktails'], operationId: 'deleteCocktailPublicLink', description: 'Delete a cocktail public link', summary: 'Delete public link', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function makePrivate(Request $request, string $idOrSlug): Response
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $cocktail = $cocktail->makePrivate();

        return new Response(null, 204);
    }

    #[OAT\Get(path: '/cocktails/{id}/share', tags: ['Cocktails'], operationId: 'shareCocktail', description: 'Get cocktail details in a specific shareable format', summary: 'Share cocktail', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'type', in: 'query', description: 'Share format', schema: new OAT\Schema(type: 'string', enum: ['json', 'json-ld', 'yaml', 'yml', 'xml', 'text', 'markdown', 'md'])),
        new OAT\Parameter(name: 'units', in: 'query', description: 'Units of measurement', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new OAT\JsonContent(required: ['data'], properties: [
            new OAT\Property(property: 'data', type: 'object', required: ['type', 'content'], properties: [
                new OAT\Property(property: 'type', type: 'string', example: 'json'),
                new OAT\Property(property: 'content', type: 'string', example: '<content in requested format>'),
            ]),
        ]),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function share(Request $request, string $idOrSlug): JsonResponse
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images' => function ($query) {
                $query->orderBy('sort');
            }, 'ingredients.substitutes', 'ingredients.ingredient']);

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $type = $request->get('type', 'json');
        $units = Units::tryFrom($request->get('units', ''));

        $data = SchemaDraft2::fromCocktailModel($cocktail, $units);

        $shareContent = null;

        if ($type === 'json') {
            $shareContent = json_encode($data->toDraft2Array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($type === 'json-ld') {
            $shareContent = $data->cocktail->toJSONLD();
        }

        if ($type === 'yaml' || $type === 'yml') {
            $shareContent = $data->toYAML();
        }

        if ($type === 'xml') {
            $shareContent = $data->toXML();
        }

        if ($type === 'markdown' || $type === 'md') {
            $shareContent = $data->toMarkdown();
        }

        if ($shareContent === null) {
            abort(400, 'Requested type "' . $type . '" not supported');
        }

        return response()->json([
            'data' => [
                'type' => $type,
                'content' => $shareContent,
            ]
        ]);
    }

    #[OAT\Get(path: '/cocktails/{id}/similar', tags: ['Cocktails'], operationId: 'showSimilarCocktails', description: 'Shows similar cocktails to the given cocktail. Prefers cocktails with same base ingredient.', summary: 'Show similar cocktails', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function similar(CocktailRepository $cocktailRepo, Request $request, string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->with('ingredients.ingredient')->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $relatedCocktails = $cocktailRepo->getSimilarCocktails($cocktail, $request->get('limit', 5));

        return CocktailResource::collection($relatedCocktails);
    }

    #[OAT\Post(path: '/cocktails/{id}/copy', tags: ['Cocktails'], operationId: 'copyCocktail', description: 'Create a copy of a cocktail', summary: 'Copy cocktail', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function copy(string $idOrSlug, CocktailService $cocktailService, ImageService $imageservice, Request $request): JsonResponse
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images', 'tags', 'ingredients.substitutes', 'utensils']);

        if ($request->user()->cannot('show', $cocktail) || $request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        // Copy images
        $imageDTOs = [];
        foreach ($cocktail->images as $image) {
            if ($imageContents = file_get_contents($image->getPath())) {
                try {
                    $imageDTOs[] = new ImageRequest(
                        image: $imageContents,
                        copyright: $image->copyright,
                        sort: $image->sort,
                    );
                } catch (Throwable $e) {
                }
            }
        }

        $images = array_map(
            fn ($image) => $image->id,
            $imageservice->uploadAndSaveImages($imageDTOs, $request->user()->id)
        );

        // Copy ingredients
        $ingredients = [];
        foreach ($cocktail->ingredients as $ingredient) {
            $substitutes = [];
            foreach ($ingredient->substitutes as $sub) {
                $substitutes[] = new SubstituteDTO(
                    $sub->ingredient_id,
                    $sub->amount,
                    $sub->amount_max,
                    $sub->units,
                );
            }

            $ingredient = new IngredientDTO(
                $ingredient->ingredient_id,
                null,
                $ingredient->amount,
                $ingredient->units,
                $ingredient->sort,
                $ingredient->optional,
                $ingredient->is_specified,
                $substitutes,
                $ingredient->amount_max,
                $ingredient->note
            );
            $ingredients[] = $ingredient;
        }

        $cocktailDTO = new CocktailDTO(
            $cocktail->name . ' Copy',
            $cocktail->instructions,
            $request->user()->id,
            $cocktail->bar_id,
            $cocktail->description,
            $cocktail->source,
            $cocktail->garnish,
            $cocktail->glass_id,
            $cocktail->cocktail_method_id,
            $cocktail->tags->pluck('name')->toArray(),
            $ingredients,
            $images,
            $cocktail->utensils->pluck('id')->toArray(),
        );

        try {
            $cocktail = $cocktailService->createCocktail($cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    #[OAT\Get(path: '/cocktails/{id}/prices', tags: ['Cocktails'], operationId: 'getCocktailPrices', summary: 'Show cocktail prices', description: 'Show calculated prices categorized by bar price categories. Prices are calculated using ingredient prices. If price category is missing, the ingredients don\'t have a price in that category. If there are multiple prices in category, the minimum price is used. Keep in mind that the price is just an estimate and might not be accurate.', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\CocktailPrice::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function prices(Request $request, string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail()
            ->load(['ingredients.ingredient.prices.priceCategory']);

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $results = [];
        $categories = PriceCategory::where('bar_id', $cocktail->bar_id)->get();

        foreach ($categories as $category) {
            $result = new CocktailPriceResource(new CocktailPrice($category, $cocktail));

            $results[] = $result;
        }

        return CocktailPriceResource::collection($results);
    }
}
