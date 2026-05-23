<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Bar;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Validator;
use Kami\Cocktail\Rules\ResourceBelongsToBar;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CollectionRequest;
use Kami\Cocktail\Http\Resources\CollectionResource;
use Kami\Cocktail\Http\Filters\CollectionQueryFilter;
use BarAssistant\Application\Cocktail\CollectionService;
use Kami\Cocktail\Models\Collection as CocktailCollection;
use BarAssistant\Application\Cocktail\DTO\CreateCollection;
use BarAssistant\Application\Cocktail\DTO\UpdateCollection;
use BarAssistant\Application\Cocktail\DTO\SyncCollectionCocktails;

class CollectionController extends Controller
{
    #[OAT\Get(path: '/collections', tags: ['Collections'], operationId: 'listCollections', description: 'Show a list of all user collections in a specific bar', summary: 'List collections', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'integer'),
            new OAT\Property(property: 'name', type: 'string'),
            new OAT\Property(property: 'cocktail_id', type: 'string'),
        ])),
        new OAT\Parameter(name: 'include', in: 'query', description: 'Include additional relationships. Available relations: `cocktails`.', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CollectionResource::class),
    ])]
    public function index(): JsonResource
    {
        $collections = (new CollectionQueryFilter())->get();

        return CollectionResource::collection($collections);
    }

    #[OAT\Get(path: '/bars/{id}/collections', tags: ['Collections'], operationId: 'listSharedCollections', description: 'Show a list of all collections that users shared with the bar', summary: 'List shared collections', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CollectionResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function shared(Request $request, int $id): JsonResource
    {
        $bar = Bar::findOrFail($id);

        if ($request->user()->cannot('show', $bar)) {
            abort(403);
        }

        $collections = CocktailCollection::where('is_bar_shared', true)
            ->select('collections.*')
            ->join('bar_memberships', 'bar_memberships.id', '=', 'collections.bar_membership_id')
            ->where('bar_memberships.bar_id', $id)
            ->orderBy('name')
            ->with('cocktails', 'barMembership.user')
            ->get();

        return CollectionResource::collection($collections);
    }

    #[OAT\Get(path: '/collections/{id}', tags: ['Collections'], operationId: 'showCollection', description: 'Show a specific collection', summary: 'Show collection', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id or slug of a resource', schema: new OAT\Schema(type: 'integer')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CollectionResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $collection = CocktailCollection::findOrFail($id)->load('barMembership', 'cocktails');

        if ($request->user()->cannot('show', $collection)) {
            abort(403);
        }

        return new CollectionResource($collection);
    }

    #[OAT\Post(path: '/collections', tags: ['Collections'], operationId: 'saveCollection', description: 'Create a new collection', summary: 'Create collection', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CollectionRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(CollectionService $collectionService, CollectionRequest $request): Response
    {
        if ($request->user()->cannot('create', CocktailCollection::class)) {
            abort(403);
        }

        $cocktailIds = $request->post('cocktails', []);

        $barMembership = $request->user()->getBarMembership(bar()->id);

        Validator::make($cocktailIds, [
            '*' => [new ResourceBelongsToBar($barMembership->bar_id, 'cocktails')],
        ])->validate();

        $collectionResult = $collectionService->createCollection(new CreateCollection(
            barId: bar()->id,
            memberId: $barMembership->id,
            name: $request->input('name'),
            description: $request->input('description'),
            isBarShared: $request->boolean('is_bar_shared'),
            cocktailIds: $cocktailIds,
        ));

        return new Response(status: 201, headers: ['Location' => route('collection.show', $collectionResult->id, false)]);
    }

    #[OAT\Put(path: '/collections/{id}', tags: ['Collections'], operationId: 'updateCollection', description: 'Update a specific collection', summary: 'Update collection', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CollectionRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(CollectionService $collectionService, CollectionRequest $request, int $id): Response
    {
        $collection = CocktailCollection::findOrFail($id)->load('barMembership');

        if ($request->user()->cannot('edit', $collection)) {
            abort(403);
        }

        $collectionService->updateCollection(new UpdateCollection(
            collectionId: $id,
            name: $request->input('name'),
            description: $request->input('description'),
            isBarShared: $request->boolean('is_bar_shared'),
        ));

        return new Response(status: 204);
    }

    #[OAT\Put(path: '/collections/{id}/cocktails', tags: ['Collections'], operationId: 'syncCocktailsInCollection', summary: 'Sync cocktails in a collection', description: 'Used to add and remove cocktails in a collection. To delete all cocktails pass an empty array.', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'cocktails', type: 'array', items: new OAT\Items(type: 'integer')),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    public function sync(CollectionService $collectionService, Request $request, int $id): Response
    {
        $collection = CocktailCollection::findOrFail($id)->load('barMembership');

        if ($request->user()->cannot('edit', $collection)) {
            abort(403);
        }

        $cocktailIds = $request->post('cocktails', []);

        Validator::make($cocktailIds, [
            '*' => [new ResourceBelongsToBar($collection->barMembership->bar_id, 'cocktails')],
        ])->validate();

        try {
            $collectionService->syncCocktails(new SyncCollectionCocktails(
                collectionId: $id,
                cocktailIds: $cocktailIds,
            ));
        } catch (Throwable) {
            abort(500, 'Unable to add cocktails to collection!');
        }

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/collections/{id}', tags: ['Collections'], operationId: 'deleteCollection', description: 'Delete a specific collection', summary: 'Delete collection', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(CollectionService $collectionService, Request $request, int $id): Response
    {
        $collection = CocktailCollection::findOrFail($id);

        if ($request->user()->cannot('delete', $collection)) {
            abort(403);
        }

        $collectionService->deleteCollection($id);

        return new Response(null, 204);
    }
}
