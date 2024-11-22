<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Utensil;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Http\Requests\UtensilRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\UtensilResource;

class UtensilsController extends Controller
{
    #[OAT\Get(path: '/utensils', tags: ['Utensils'], operationId: 'listUtensils', summary: 'Show a list of utensils', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\Utensil::class),
    ])]
    public function index(): JsonResource
    {
        $utensils = Utensil::orderBy('name')->filterByBar()->get();

        return UtensilResource::collection($utensils);
    }

    #[OAT\Get(path: '/utensils/{id}', tags: ['Utensils'], operationId: 'showUtensil', summary: 'Show utensil', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Utensil::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('show', $utensil)) {
            abort(403);
        }

        return new UtensilResource($utensil);
    }

    #[OAT\Post(path: '/utensils', tags: ['Utensils'], operationId: 'saveUtensil', summary: 'Create a new utensil', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UtensilRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Utensil::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(UtensilRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Utensil::class)) {
            abort(403);
        }

        $utensil = new Utensil();
        $utensil->name = $request->input('name');
        $utensil->description = $request->input('description');
        $utensil->bar_id = bar()->id;
        $utensil->save();

        return (new UtensilResource($utensil))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('utensils.show', $utensil->id));
    }

    #[OAT\Put(path: '/utensils/{id}', tags: ['Utensils'], operationId: 'updateUtensil', summary: 'Update a specific utensil', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UtensilRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Utensil::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(int $id, UtensilRequest $request): JsonResource
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('edit', $utensil)) {
            abort(403);
        }

        $utensil->name = $request->input('name');
        $utensil->description = $request->input('description');
        $utensil->updated_at = now();
        $utensil->save();

        return new UtensilResource($utensil);
    }

    #[OAT\Delete(path: '/utensils/{id}', tags: ['Utensils'], operationId: 'deleteUtensil', summary: 'Delete specific utensil', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('delete', $utensil)) {
            abort(403);
        }

        $utensil->delete();

        return new Response(null, 204);
    }
}
