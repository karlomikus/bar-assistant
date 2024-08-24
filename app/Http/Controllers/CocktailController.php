<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Spatie\ArrayToXml\ArrayToXml;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Support\Facades\Validator;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Kami\Cocktail\DTO\Image\Image as ImageDTO;
use Kami\Cocktail\Services\Image\ImageService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use Kami\Cocktail\Repository\CocktailRepository;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Filters\CocktailQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\DTO\Cocktail\Cocktail as CocktailDTO;
use Kami\Cocktail\External\Model\Schema as SchemaDraft2;
use Kami\Cocktail\DTO\Cocktail\Ingredient as IngredientDTO;
use Kami\Cocktail\DTO\Cocktail\Substitute as SubstituteDTO;

class CocktailController extends Controller
{
    #[OAT\Get(path: '/cocktails', tags: ['Cocktails'], summary: 'Show a list of cocktails', parameters: [
        new BAO\Parameters\BarIdParameter(),
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
            new OAT\Property(property: 'specific_ingredients', type: 'string'),
            new OAT\Property(property: 'ignore_ingredients', type: 'string'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`, `average_rating`, `user_rating`, `abv`, `total_ingredients`, `missing_ingredients`, `favorited_at`.', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'include', in: 'query', description: 'Include additional relationships. Available relations: `glass`, `method`, `user`, `navigation`, `utensils`, `createdUser`, `updatedUser`, `images`, `tags`, `ingredients.ingredient`, `ratings`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
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

    #[OAT\Get(path: '/cocktails/{id}', tags: ['Cocktails'], summary: 'Show a specific cocktail', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(string $idOrSlug, Request $request): JsonResource
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->withRatings($request->user()->id)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils', 'ratings']);

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        return new CocktailResource($cocktail);
    }

    #[OAT\Post(path: '/cocktails', tags: ['Cocktails'], summary: 'Create a new cocktail', parameters: [
        new BAO\Parameters\BarIdParameter(),
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
    public function store(CocktailService $cocktailService, CocktailRequest $request): JsonResponse
    {
        Validator::make($request->post('ingredients', []), [
            '*.ingredient_id' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
        ])->validate();

        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $cocktailDTO = CocktailDTO::fromIlluminateRequest($request, bar()->id);

        try {
            $cocktail = $cocktailService->createCocktail($cocktailDTO);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load(['ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes', 'method', 'createdUser', 'updatedUser', 'collections', 'utensils', 'ratings']);

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    #[OAT\Put(path: '/cocktails/{id}', tags: ['Cocktails'], summary: 'Update a specific cocktail', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CocktailRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Cocktail::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function update(CocktailService $cocktailService, CocktailRequest $request, int $id): JsonResource
    {
        $cocktail = Cocktail::findOrFail($id);

        Validator::make($request->post('ingredients', []), [
            '*.ingredient_id' => [new ResourceBelongsToBar($cocktail->bar_id, 'ingredients')],
        ])->validate();

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $cocktailDTO = CocktailDTO::fromIlluminateRequest($request, $cocktail->bar_id);

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

    #[OAT\Delete(path: '/cocktails/{id}', tags: ['Cocktails'], summary: 'Delete a specific cocktail', parameters: [
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

    #[OAT\Post(path: '/cocktails/{id}/toggle-favorite', tags: ['Cocktails'], summary: 'Toggle cocktail as favorite', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
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

    #[OAT\Post(path: '/cocktails/{id}/public-link', tags: ['Cocktails'], summary: 'Create a public ID for cocktail', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
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

    #[OAT\Delete(path: '/cocktails/{id}/public-link', tags: ['Cocktails'], summary: 'Delete cocktail public link', parameters: [
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

    #[OAT\Get(path: '/cocktails/{id}/share', tags: ['Cocktails'], summary: 'Share a cocktail', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'type', in: 'query', description: 'Share format', schema: new OAT\Schema(type: 'string', enum: ['json', 'json-ld', 'yaml', 'yml', 'xml', 'text', 'markdown', 'md'])),
        new OAT\Parameter(name: 'units', in: 'query', description: 'Units of measurement', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
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
            }, 'ingredients.substitutes', 'ingredients.ingredient.category']);

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $type = $request->get('type', 'json');
        $units = Units::tryFrom($request->get('units', ''));

        $data = SchemaDraft2::fromCocktailModel($cocktail, true)->toDraft2Array();

        $shareContent = null;

        if ($type === 'json') {
            $shareContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($type === 'json-ld') {
            $shareContent = json_encode($cocktail->asJsonLDSchema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($type === 'yaml' || $type === 'yml') {
            $shareContent = Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        }

        if ($type === 'xml') {
            $shareContent = ArrayToXml::convert($data, 'cocktail', xmlEncoding: 'UTF-8');
        }

        if ($type === 'text') {
            $shareContent = view('recipe_text_template', compact('cocktail', 'units'))->render();
        }

        if ($type === 'markdown' || $type === 'md') {
            $shareContent = view('md_recipe_template', compact('cocktail', 'units'))->render();
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

    public function similar(CocktailRepository $cocktailRepo, Request $request, string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->with('ingredients.ingredient')->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $relatedCocktails = $cocktailRepo->getSimilarCocktails($cocktail, $request->get('limit', 5));

        return CocktailResource::collection($relatedCocktails);
    }

    #[OAT\Post(path: '/cocktails/{id}/copy', tags: ['Cocktails'], summary: 'Copy cocktail', parameters: [
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
            try {
                $imageDTOs[] = new ImageDTO(
                    file_get_contents($image->getPath()),
                    $image->copyright,
                    $image->sort,
                );
            } catch (Throwable $e) {
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
}
