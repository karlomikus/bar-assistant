<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Utensil;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Http\Requests\UtensilRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\UtensilResource;
use BarAssistant\Application\Cocktail\UtensilService;
use BarAssistant\Application\Cocktail\DTO\CreateUtensil;
use BarAssistant\Application\Cocktail\DTO\UpdateUtensil;

class UtensilsController extends Controller
{
    #[OAT\Get(path: '/utensils', tags: ['Utensils'], operationId: 'listUtensils', description: 'List all utensils in a bar', summary: 'List utensils', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(UtensilResource::class),
    ])]
    public function index(): JsonResource
    {
        $utensils = Utensil::orderBy('name')->filterByBar()->get();

        return UtensilResource::collection($utensils);
    }

    #[OAT\Get(path: '/utensils/{id}', tags: ['Utensils'], operationId: 'showUtensil', description: 'Show a single utensil', summary: 'Show utensil', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(UtensilResource::class),
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

    #[OAT\Post(path: '/utensils', tags: ['Utensils'], operationId: 'saveUtensil', description: 'Create a new utensil', summary: 'Create utensil', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UtensilRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(UtensilResource::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(UtensilService $utensilService, UtensilRequest $request): Response
    {
        if ($request->user()->cannot('create', Utensil::class)) {
            abort(403);
        }

        $utensilRequest = BAO\Schemas\UtensilRequest::fromLaravelRequest($request);

        $utensilResult = $utensilService->createUtensil(new CreateUtensil(
            barId: bar()->id,
            name: $utensilRequest->name,
            description: $utensilRequest->description,
        ));

        return new Response(status: 201, headers: ['Location' => route('utensils.show', $utensilResult->id, false)]);
    }

    #[OAT\Put(path: '/utensils/{id}', tags: ['Utensils'], operationId: 'updateUtensil', description: 'Update a single utensil', summary: 'Update utensil', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\UtensilRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(UtensilService $utensilService, int $id, UtensilRequest $request): Response
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('edit', $utensil)) {
            abort(403);
        }

        $utensilRequest = BAO\Schemas\UtensilRequest::fromLaravelRequest($request);

        $utensilService->updateUtensil(new UpdateUtensil(
            utensilId: $id,
            name: $utensilRequest->name,
            description: $utensilRequest->description,
        ));

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/utensils/{id}', tags: ['Utensils'], operationId: 'deleteUtensil', description: 'Delete a single utensil', summary: 'Delete utensil', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(UtensilService $utensilService, Request $request, int $id): Response
    {
        $utensil = Utensil::findOrFail($id);

        if ($request->user()->cannot('delete', $utensil)) {
            abort(403);
        }

        $utensilService->deleteUtensil($id);

        return new Response(null, 204);
    }
}
