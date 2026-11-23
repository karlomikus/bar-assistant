<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Database\Eloquent\Builder;
use Kami\Cocktail\Http\Requests\GlassRequest;
use Kami\Cocktail\Http\Resources\GlassResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Filters\GlassQueryFilter;
use BarAssistant\Application\Cocktail\GlassService;
use BarAssistant\Application\Cocktail\DTO\CreateGlass;
use BarAssistant\Application\Cocktail\DTO\UpdateGlass;

class GlassController extends Controller
{
    #[OAT\Get(path: '/glasses', tags: ['Glasses'], operationId: 'listGlassware', description: 'Show a list of all glassware in the bar', summary: 'List glassware', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'name', type: 'string'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(GlassResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function index(): JsonResource
    {
        $glasses = new GlassQueryFilter()->get();

        return GlassResource::collection($glasses);
    }

    #[OAT\Get(path: '/glasses/{id}', tags: ['Glasses'], operationId: 'showGlassware', description: 'Show a specific glassware', summary: 'Show glassware', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(GlassResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $glass = Glass::withCount([
            'cocktails' => static fn (Builder $query): Builder => $query->whereColumn('cocktails.bar_id', 'glasses.bar_id'),
        ])->with('images')->findOrFail($id);
        $user = $request->user();

        if ($user === null || $user->cannot('show', $glass)) {
            abort(403);
        }

        return new GlassResource($glass);
    }

    #[OAT\Post(path: '/glasses', tags: ['Glasses'], operationId: 'saveGlassware', description: 'Create a new glassware', summary: 'Create glassware', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\GlassRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(response: 201, headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string', format: 'uri-reference')),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\ValidationFailedResponse]
    public function store(GlassService $glassService, GlassRequest $request): Response
    {
        $user = $request->user();
        if ($user === null || $user->cannot('create', Glass::class)) {
            abort(403);
        }

        $glassRequest = BAO\Schemas\GlassRequest::fromLaravelRequest($request);

        $glassResult = $glassService->createGlass(new CreateGlass(
            barId: bar()->id,
            name: $glassRequest->name,
            description: $glassRequest->description,
            volume: $glassRequest->volume,
            units: $glassRequest->volumeUnits,
            images: $glassRequest->images,
        ));

        return new Response(status: 201, headers: ['Location' => route('glasses.show', $glassResult->id, false)]);
    }

    #[OAT\Put(path: '/glasses/{id}', tags: ['Glasses'], operationId: 'updateGlassware', description: 'Update a specific glassware', summary: 'Update glassware', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\GlassRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(response: 204)]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function update(GlassService $glassService, int $id, GlassRequest $request): Response
    {
        $glass = Glass::findOrFail($id);
        $user = $request->user();

        if ($user === null || $user->cannot('edit', $glass)) {
            abort(403);
        }

        $glassRequest = BAO\Schemas\GlassRequest::fromLaravelRequest($request);

        $glassService->updateGlass(new UpdateGlass(
            glassId: $id,
            name: $glassRequest->name,
            description: $glassRequest->description,
            volume: $glassRequest->volume,
            units: $glassRequest->volumeUnits,
            images: $glassRequest->images,
        ));

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/glasses/{id}', tags: ['Glasses'], operationId: 'deleteGlassware', description: 'Delete a specific glassware', summary: 'Delete glassware', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(response: 204)]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(GlassService $glassService, Request $request, int $id): Response
    {
        $glass = Glass::findOrFail($id);
        $user = $request->user();

        if ($user === null || $user->cannot('delete', $glass)) {
            abort(403);
        }

        $glassService->deleteGlass($id);

        return new Response(status: 204);
    }
}
