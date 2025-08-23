<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Glass;
use Kami\Cocktail\Models\Image;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Http\Requests\GlassRequest;
use Kami\Cocktail\Http\Resources\GlassResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Filters\GlassQueryFilter;

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
    public function index(): JsonResource
    {
        $glasses = (new GlassQueryFilter())->get();

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
        $glass = Glass::withCount('cocktails')->with('images')->findOrFail($id);

        if ($request->user()->cannot('show', $glass)) {
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
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(GlassResource::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(GlassRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Glass::class)) {
            abort(403);
        }

        $glassRequest = BAO\Schemas\GlassRequest::fromLaravelRequest($request);

        $glass = $glassRequest->toLaravelModel();
        $glass->bar_id = bar()->id;
        $glass->save();

        if (count($glassRequest->images) > 0) {
            try {
                $imageModels = Image::findOrFail($glassRequest->images);
                $glass->attachImages($imageModels);
            } catch (Throwable $e) {
                abort(500, $e->getMessage());
            }
        }

        return (new GlassResource($glass))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('glasses.show', $glass->id));
    }

    #[OAT\Put(path: '/glasses/{id}', tags: ['Glasses'], operationId: 'updateGlassware', description: 'Update a specific glassware', summary: 'Update glassware', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\GlassRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(GlassResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(int $id, GlassRequest $request): JsonResource
    {
        $glass = Glass::findOrFail($id);

        if ($request->user()->cannot('edit', $glass)) {
            abort(403);
        }

        $glassRequest = BAO\Schemas\GlassRequest::fromLaravelRequest($request);

        $glass = $glassRequest->toLaravelModel($glass);
        $glass->updated_at = now();
        $glass->save();

        if (count($glassRequest->images) > 0) {
            try {
                $imageModels = Image::findOrFail($glassRequest->images);
                $glass->attachImages($imageModels);
            } catch (Throwable $e) {
                abort(500, $e->getMessage());
            }
        }

        if (!empty(config('scout.driver'))) {
            $glass->cocktails->each(fn ($cocktail) => $cocktail->searchable());
        }

        return new GlassResource($glass);
    }

    #[OAT\Delete(path: '/glasses/{id}', tags: ['Glasses'], operationId: 'deleteGlassware', description: 'Delete a specific glassware', summary: 'Delete glassware', parameters: [
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
