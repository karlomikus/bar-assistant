<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailMethodRequest;
use Kami\Cocktail\Http\Resources\CocktailMethodResource;
use Kami\Cocktail\Http\Filters\CocktailMethodQueryFilter;
use BarAssistant\Application\Cocktail\CocktailMethodService;
use BarAssistant\Application\Cocktail\DTO\CreateCocktailMethod;
use BarAssistant\Application\Cocktail\DTO\UpdateCocktailMethod;

class CocktailMethodController extends Controller
{
    #[OAT\Get(path: '/cocktail-methods', tags: ['Cocktail method'], operationId: 'listCocktailMethods', description: 'Show a list of all cocktail methods in a bar', summary: 'List methods', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
        new OAT\Parameter(name: 'filter', in: 'query', description: 'Filter by attributes', explode: true, style: 'deepObject', schema: new OAT\Schema(type: 'object', properties: [
            new OAT\Property(property: 'name', type: 'string'),
        ])),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(CocktailMethodResource::class),
    ])]
    public function index(): JsonResource
    {
        $methods = (new CocktailMethodQueryFilter())->get();

        return CocktailMethodResource::collection($methods);
    }

    #[OAT\Get(path: '/cocktail-methods/{id}', tags: ['Cocktail method'], operationId: 'showCocktailMethod', description: 'Show a specific cocktail method', summary: 'Show method', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(CocktailMethodResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $method = CocktailMethod::withCount('cocktails')->findOrFail($id);

        if ($request->user()->cannot('show', $method)) {
            abort(403);
        }

        return new CocktailMethodResource($method);
    }

    #[OAT\Post(path: '/cocktail-methods', tags: ['Cocktail method'], operationId: 'saveCocktailMethod', description: 'Create a new cocktail method', summary: 'Create method', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CocktailMethodRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(CocktailMethodService $cocktailMethodService, CocktailMethodRequest $request): Response
    {
        if ($request->user()->cannot('create', CocktailMethod::class)) {
            abort(403);
        }

        $methodRequest = BAO\Schemas\CocktailMethodRequest::fromLaravelRequest($request);

        $methodResult = $cocktailMethodService->createCocktailMethod(new CreateCocktailMethod(
            barId: bar()->id,
            name: $methodRequest->name,
            dilutionPercentage: $methodRequest->dilutionPercentage,
            description: $methodRequest->description,
        ));

        return new Response(status: 201, headers: ['Location' => route('cocktail-methods.show', $methodResult->id, false)]);
    }

    #[OAT\Put(path: '/cocktail-methods/{id}', tags: ['Cocktail method'], operationId: 'updateCocktailMethod', description: 'Update a specific cocktail method', summary: 'Update method', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CocktailMethodRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(CocktailMethodService $cocktailMethodService, int $id, CocktailMethodRequest $request): Response
    {
        $method = CocktailMethod::findOrFail($id);

        if ($request->user()->cannot('edit', $method)) {
            abort(403);
        }

        $methodRequest = BAO\Schemas\CocktailMethodRequest::fromLaravelRequest($request);

        $cocktailMethodService->updateCocktailMethod(new UpdateCocktailMethod(
            id: $id,
            name: $methodRequest->name,
            dilutionPercentage: $methodRequest->dilutionPercentage,
            description: $methodRequest->description,
        ));

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/cocktail-methods/{id}', tags: ['Cocktail method'], operationId: 'deleteCocktailMethod', description: 'Delete a specific cocktail method', summary: 'Delete method', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(CocktailMethodService $cocktailMethodService, Request $request, int $id): Response
    {
        $method = CocktailMethod::findOrFail($id);

        if ($request->user()->cannot('delete', $method)) {
            abort(403);
        }

        $cocktailMethodService->deleteCocktailMethod($id);

        return new Response(null, 204);
    }
}
