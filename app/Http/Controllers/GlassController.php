<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Glass;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Http\Requests\GlassRequest;
use Kami\Cocktail\Http\Resources\GlassResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Filters\GlassQueryFilter;

class GlassController extends Controller
{
    #[OAT\Get(path: '/glasses', tags: ['Glasses'], summary: 'Show a list of glass types', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'name', type: 'string'),
        ])),
        new OAT\Parameter(name: 'sort', in: 'query', description: 'Sort by attributes. Available attributes: `name`, `created_at`.', schema: new OAT\Schema(type: 'string')),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\Glass::class),
    ])]
    public function index(): JsonResource
    {
        $glasses = (new GlassQueryFilter())->get();

        return GlassResource::collection($glasses);
    }

    #[OAT\Get(path: '/glasses/{id}', tags: ['Glasses'], summary: 'Show glass', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Glass::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $glass = Glass::withCount('cocktails')->findOrFail($id);

        if ($request->user()->cannot('show', $glass)) {
            abort(403);
        }

        return new GlassResource($glass);
    }

    #[OAT\Post(path: '/glasses', tags: ['Glasses'], summary: 'Create a new glass', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\GlassRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Glass::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(GlassRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Glass::class)) {
            abort(403);
        }

        $glass = BAO\Schemas\GlassRequest::fromLaravelRequest($request)->toLaravelModel();
        $glass->bar_id = bar()->id;
        $glass->save();

        return (new GlassResource($glass))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('glasses.show', $glass->id));
    }

    #[OAT\Put(path: '/glasses/{id}', tags: ['Glasses'], summary: 'Update glass', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\GlassRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Glass::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(int $id, GlassRequest $request): JsonResource
    {
        $glass = Glass::findOrFail($id);

        if ($request->user()->cannot('edit', $glass)) {
            abort(403);
        }

        $glass = BAO\Schemas\GlassRequest::fromLaravelRequest($request)->toLaravelModel($glass);
        $glass->updated_at = now();
        $glass->save();

        $glass->cocktails->each(fn ($cocktail) => $cocktail->searchable());

        return new GlassResource($glass);
    }

    #[OAT\Delete(path: '/glasses/{id}', tags: ['Glasses'], summary: 'Delete glass', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $glass = Glass::findOrFail($id);

        if ($request->user()->cannot('delete', $glass)) {
            abort(403);
        }

        $glass->delete();

        return new Response(null, 204);
    }
}
