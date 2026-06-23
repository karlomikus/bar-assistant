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
use Kami\Cocktail\Models\CocktailPrice;
use Kami\Cocktail\Models\PriceCategory;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Support\Facades\Validator;
use Kami\RecipeUtils\UnitConverter\Units;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use BarAssistant\Application\Image\ImageService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use BarAssistant\Application\Bar\FavoriteService;
use Kami\Cocktail\Http\Resources\CocktailResource;
use BarAssistant\Application\Image\DTO\CreateImage;
use Kami\Cocktail\Http\Filters\CocktailQueryFilter;
use Kami\Cocktail\Services\Image\ImageUploadService;
use BarAssistant\Application\Bar\DTO\FavoriteRequest;
use BarAssistant\Application\Cocktail\CocktailService;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use BarAssistant\Application\Cocktail\DTO\CopyCocktail;
use Kami\Cocktail\Http\Resources\CocktailPriceResource;
use BarAssistant\Application\Cocktail\DTO\CreateCocktail;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktail;
use Kami\Cocktail\External\Model\Schema as SchemaExternal;
use BarAssistant\Application\Cocktail\DTO\CocktailIngredient;
use Kami\Cocktail\OpenAPI\Schemas\CocktailRequest as CocktailDTO;
use BarAssistant\Application\Cocktail\DTO\ForceCocktailVisibility;
use BarAssistant\Application\Cocktail\DTO\ToggleCocktailVisibility;
use BarAssistant\Application\Cocktail\DTO\CocktailIngredientSubstitute;
use Kami\Cocktail\Services\CocktailService as InfrastructureCocktailService;

class CocktailController extends Controller
{
    #[OAT\Get(path: '/cocktails', tags: ['Cocktails'], operationId: 'listCocktails', description: 'Show a list of all cocktails in a bar', summary: 'List cocktails', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes. You can specify multiple matching filter values by passing a comma separated list of values.', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'string', description: 'Filter by cocktail ID(s)'),
            new OAT\Property(property: 'name', type: 'string', description: 'Filter by cocktail names(s) (fuzzy search)'),
            new OAT\Property(property: 'ingredient_name', type: 'string', description: 'Filter by cocktail ingredient names(s) (fuzzy search)'),
            new OAT\Property(property: 'tag_id', type: 'string', description: 'Filter by tag ID(s)'),
            new OAT\Property(property: 'created_user_id', type: 'string', description: 'Filter by creator ID(s)'),
            new OAT\Property(property: 'glass_id', type: 'string', description: 'Filter by glass ID(s)'),
            new OAT\Property(property: 'cocktail_method_id', type: 'string', description: 'Filter by cocktail method ID(s)'),
            new OAT\Property(property: 'collection_id', type: 'string', description: 'Filter by collection ID(s)'),
            new OAT\Property(property: 'favorites', type: 'boolean', description: 'Show only user favorites'),
            new OAT\Property(property: 'on_shelf', type: 'boolean', description: 'Show only cocktails on the user\'s shelf'),
            new OAT\Property(property: 'bar_shelf', type: 'boolean', description: 'Show only cocktails on the bar shelf'),
            new OAT\Property(property: 'user_shelves', type: 'string', description: 'Show only cocktails on the user\'s shelves. Comma separated list of user IDs'),
            new OAT\Property(property: 'shelf_ingredients', type: 'string', description: 'Show only cocktails that can be made with the given ingredients. Used as on-the-fly custom shelf filter'),
            new OAT\Property(property: 'is_public', type: 'boolean', description: 'Show only cocktails with public links'),
            new OAT\Property(property: 'user_rating_min', type: 'number', description: 'Filter by greater than or equal user rating'),
            new OAT\Property(property: 'user_rating_max', type: 'number', description: 'Filter by less than or equal user rating'),
            new OAT\Property(property: 'average_rating_min', type: 'number', description: 'Filter by greater than or equal average rating'),
            new OAT\Property(property: 'average_rating_max', type: 'number', description: 'Filter by less than or equal average rating'),
            new OAT\Property(property: 'abv_min', type: 'number', description: 'Filter by greater than or equal ABV'),
            new OAT\Property(property: 'abv_max', type: 'number', description: 'Filter by less than or equal ABV'),
            new OAT\Property(property: 'main_ingredient_id', type: 'string', description: 'Show only cocktails whose main ingredient is in the given list. Comma separated list of ingredient IDs'),
            new OAT\Property(property: 'ingredient_id', type: 'string', description: 'Show only cocktails that contain this ingredient. Comma separated list of ingredient IDs'),
            new OAT\Property(property: 'ingredient_substitute_id', type: 'string', description: 'Show only cocktails that contain this substitute. Comma separated list of ingredient IDs'),
            new OAT\Property(property: 'total_ingredients', type: 'number', description: 'Filter by total number of ingredients'),
            new OAT\Property(property: 'missing_ingredients', type: 'number', description: 'Filter by total number of missing ingredients'),
            new OAT\Property(property: 'missing_bar_ingredients', type: 'number', description: 'Filter by total number of missing bar ingredients'),
            new OAT\Property(property: 'specific_ingredients', type: 'string', description: 'Show cocktails that contain given ingredient ID(s)'),
            new OAT\Property(property: 'ignore_ingredients', type: 'string', description: 'Show cocktails that do not contain given ingredient ID(s)'),
            new OAT\Property(property: 'locked_user_cocktails', type: 'boolean', description: 'Show only cocktails that user can\'t make'),
            new OAT\Property(property: 'locked_bar_cocktails', type: 'boolean', description: 'Show only cocktails that bar can\'t make'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`, `average_rating`, `user_rating`, `abv`, `total_ingredients`, `missing_ingredients`, `missing_bar_ingredients`, `favorited_at`, `random`.', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'include', in: 'query', description: 'Include additional relationships. Available relations: `glass`, `method`, `user`, `navigation`, `utensils`, `createdUser`, `updatedUser`, `images`, `tags`, `ingredients.ingredient`, `ratings`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(CocktailResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(InfrastructureCocktailService $service, Request $request): JsonResource
    {
        try {
            $cocktails = new CocktailQueryFilter($service);
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        $cocktails = $cocktails->paginate($request->input('per_page', 25));

        return CocktailResource::collection($cocktails->withQueryString());
    }

    #[OAT\Get(path: '/cocktails/{id}', tags: ['Cocktails'], operationId: 'showCocktail', description: 'Show details of a specific cocktail', summary: 'Show cocktail', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CocktailResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(string $idOrSlug, Request $request): JsonResource
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->withRatings($request->user()->id)
            ->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $cocktail->loadDefaultRelations();

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
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\ValidationFailedResponse]
    public function store(CocktailService $cocktailService, CocktailRequest $request): Response
    {
        Validator::make($request->input('ingredients', []), [
            '*.ingredient_id' => [new ResourceBelongsToBar(bar()->id, 'ingredients')],
        ])->validate();

        if ($request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $cocktailRequest = CocktailDTO::fromIlluminateRequest($request);
        $ingredientStrengths = Ingredient::whereIn('id', collect($cocktailRequest->ingredients)->pluck('id'))->pluck('strength', 'id');

        $ingredients = [];
        foreach ($cocktailRequest->ingredients as $requestIngredientDTO) {
            $substitutes = [];
            foreach ($requestIngredientDTO->substitutes as $requestSub) {
                $substitutes[] = new CocktailIngredientSubstitute(
                    ingredientId: $requestSub->ingredientId,
                    amount: $requestSub->amount,
                    units: $requestSub->units,
                    amountMax: $requestSub->amountMax,
                );
            }

            $ingredients[] = new CocktailIngredient(
                ingredientId: $requestIngredientDTO->id,
                strength: $ingredientStrengths[$requestIngredientDTO->id] ?? 0.0,
                amount: $requestIngredientDTO->amount,
                units: $requestIngredientDTO->units,
                sort: $requestIngredientDTO->sort,
                isOptional: $requestIngredientDTO->optional,
                isSpecified: $requestIngredientDTO->isSpecified,
                substitutes: $substitutes,
                amountMax: $requestIngredientDTO->amountMax,
                note: $requestIngredientDTO->note,
            );
        }

        $dilution = 0.0;
        if ($cocktailRequest->methodId) {
            $dilution = CocktailMethod::find($cocktailRequest->methodId)->dilution_percentage ?? 0.0;
        }

        $cocktailResult = $cocktailService->createCocktail(new CreateCocktail(
            barId: $cocktailRequest->barId,
            name: $cocktailRequest->name,
            instructions: $cocktailRequest->instructions,
            userId: $cocktailRequest->userId,
            dilution: $dilution,
            description: $cocktailRequest->description,
            source: $cocktailRequest->source,
            garnish: $cocktailRequest->garnish,
            glassId: $cocktailRequest->glassId,
            methodId: $cocktailRequest->methodId,
            tags: $cocktailRequest->tags,
            ingredients: $ingredients,
            images: $cocktailRequest->images,
            utensils: $cocktailRequest->utensils,
            parentCocktailId: $cocktailRequest->parentCocktailId,
            year: $cocktailRequest->year,
            author: $cocktailRequest->author,
        ));

        return new Response(status: 201, headers: ['Location' => route('cocktails.show', $cocktailResult->slug, false)]);
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
        new BAO\WrapObjectWithData(CocktailResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function update(CocktailService $cocktailService, CocktailRequest $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        Validator::make($request->input('ingredients', []), [
            '*.ingredient_id' => [new ResourceBelongsToBar($cocktail->bar_id, 'ingredients')],
        ])->validate();

        $cocktailRequest = CocktailDTO::fromIlluminateRequest($request);
        $ingredientStrengths = Ingredient::whereIn('id', collect($cocktailRequest->ingredients)->pluck('id'))->pluck('strength', 'id');

        $ingredients = [];
        foreach ($cocktailRequest->ingredients as $requestIngredientDTO) {
            $substitutes = [];
            foreach ($requestIngredientDTO->substitutes as $requestSub) {
                $substitutes[] = new CocktailIngredientSubstitute(
                    ingredientId: $requestSub->ingredientId,
                    amount: $requestSub->amount,
                    units: $requestSub->units,
                    amountMax: $requestSub->amountMax,
                );
            }

            $ingredients[] = new CocktailIngredient(
                ingredientId: $requestIngredientDTO->id,
                strength: $ingredientStrengths[$requestIngredientDTO->id] ?? 0.0,
                amount: $requestIngredientDTO->amount,
                units: $requestIngredientDTO->units,
                sort: $requestIngredientDTO->sort,
                isOptional: $requestIngredientDTO->optional,
                isSpecified: $requestIngredientDTO->isSpecified,
                substitutes: $substitutes,
                amountMax: $requestIngredientDTO->amountMax,
                note: $requestIngredientDTO->note,
            );
        }

        $dilution = 0.0;
        if ($cocktailRequest->methodId) {
            $dilution = CocktailMethod::find($cocktailRequest->methodId)->dilution_percentage ?? 0.0;
        }

        $cocktailService->updateCocktail(new UpdateCocktail(
            cocktailId: $cocktail->id,
            barId: $cocktailRequest->barId,
            name: $cocktailRequest->name,
            instructions: $cocktailRequest->instructions,
            userId: $cocktailRequest->userId,
            dilution: $dilution,
            description: $cocktailRequest->description,
            source: $cocktailRequest->source,
            garnish: $cocktailRequest->garnish,
            glassId: $cocktailRequest->glassId,
            methodId: $cocktailRequest->methodId,
            tags: $cocktailRequest->tags,
            ingredients: $ingredients,
            images: $cocktailRequest->images,
            utensils: $cocktailRequest->utensils,
            parentCocktailId: $cocktailRequest->parentCocktailId,
            year: $cocktailRequest->year,
            author: $cocktailRequest->author,
        ));

        return new Response(status: 204);
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
    public function toggleFavorite(FavoriteService $favoriteService, Request $request, int $id): JsonResponse
    {
        $cocktail = Cocktail::findOrFail($id);
        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $barMembership = $request->user()->getBarMembership($cocktail->bar_id);
        $userFavorite = $favoriteService->toggleFavorite(new FavoriteRequest($barMembership->id, $cocktail->id));

        return response()->json([
            'data' => ['id' => $id, 'is_favorited' => $userFavorite->isFavorited]
        ]);
    }

    #[OAT\Post(path: '/cocktails/{id}/public-link', tags: ['Cocktails'], operationId: 'createCocktailPublicLink', description: 'Create a public link that can be shared', summary: 'Create a public ID', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CocktailResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function makePublic(CocktailService $cocktailService, Request $request, string $idOrSlug): Response
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('sharePublic', $cocktail)) {
            abort(403);
        }

        $cocktailService->toggleVisibility(new ToggleCocktailVisibility(
            $cocktail->id,
            ForceCocktailVisibility::Public,
        ));

        return new Response(status: 201, headers: ['Location' => route('public.cocktails.show', [$cocktail->bar_id, $cocktail->slug], false)]);
    }

    #[OAT\Delete(path: '/cocktails/{id}/public-link', tags: ['Cocktails'], operationId: 'deleteCocktailPublicLink', description: 'Delete a cocktail public link', summary: 'Delete public link', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function makePrivate(CocktailService $cocktailService, Request $request, string $idOrSlug): Response
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $cocktailService->toggleVisibility(new ToggleCocktailVisibility(
            $cocktail->id,
            ForceCocktailVisibility::Private,
        ));

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
            ->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $cocktail->loadDefaultRelations();

        $type = $request->input('type', 'json');
        $units = Units::tryFrom($request->input('units', ''));

        $data = SchemaExternal::fromCocktailModel($cocktail, $units);

        $shareContent = null;

        if ($type === 'json') {
            $shareContent = json_encode($data->toSchema4Array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
        new BAO\WrapItemsWithData(CocktailResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function similar(InfrastructureCocktailService $cocktailRepo, Request $request, string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->with('ingredients.ingredient')->firstOrFail();

        if ($request->user()->cannot('show', $cocktail)) {
            abort(403);
        }

        $relatedCocktailIds = $cocktailRepo->getSimilarCocktails($cocktail, $request->get('limit', 5));
        $relatedCocktails = Cocktail::whereIn('id', $relatedCocktailIds)
            ->with(
                'images',
                'ratings',
                'ingredients.ingredient.bar',
                'bar.shelfIngredients',
            )
            ->get();

        return CocktailResource::collection($relatedCocktails);
    }

    #[OAT\Post(path: '/cocktails/{id}/copy', tags: ['Cocktails'], operationId: 'copyCocktail', description: 'Create a copy of a cocktail', summary: 'Copy cocktail', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function copy(string $idOrSlug, CocktailService $cocktailService, ImageUploadService $imageUploadService, ImageService $imageService, Request $request): Response
    {
        $cocktail = Cocktail::where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail();

        if ($request->user()->cannot('show', $cocktail) && $request->user()->cannot('create', Cocktail::class)) {
            abort(403);
        }

        $images = [];
        foreach ($cocktail->images as $image) {
            if ($imageContents = file_get_contents($image->getPath())) {
                $uploadedImage = $imageUploadService->uploadImage($imageContents);
                $imageResult = $imageService->createImage(new CreateImage(
                    imageFilePath: $uploadedImage->path,
                    imageFileExtension: $uploadedImage->extension,
                    userId: $request->user()->id,
                    sort: $image->sort,
                    copyright: $image->copyright,
                    placeholderHash: $uploadedImage->placeholderHash,
                ));

                $images[] = $imageResult->id;
            }
        }

        $newCocktailResult = $cocktailService->copyCocktail(new CopyCocktail(
            barId: bar()->id,
            cocktailId: $cocktail->id,
            userId: $request->user()->id,
            images: $images,
        ));

        return new Response(status: 201, headers: ['Location' => route('cocktails.show', $newCocktailResult->id, false)]);
    }

    #[OAT\Get(path: '/cocktails/{id}/prices', tags: ['Cocktails'], operationId: 'getCocktailPrices', summary: 'Show cocktail prices', description: 'Show calculated prices categorized by bar price categories. Prices are calculated using ingredient prices. If price category is missing, the ingredients don\'t have a price in that category. If there are multiple prices in category, the minimum price is used. Keep in mind that the price is just an estimate and might not be accurate.', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CocktailPriceResource::class),
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
