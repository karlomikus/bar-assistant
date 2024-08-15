<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailMethodRequest;
use Kami\Cocktail\Http\Resources\CocktailMethodResource;

class CocktailMethodController extends Controller
{
    #[OAT\Get(path: '/cocktail-methods', tags: ['Cocktail method'], summary: 'Show a list of all methods', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\CocktailMethod::class),
    ])]
    public function index(): JsonResource
    {
        $methods = CocktailMethod::orderBy('id')->withCount('cocktails')->filterByBar()->get();

        return CocktailMethodResource::collection($methods);
    }

    #[OAT\Get(path: '/cocktail-methods/{id}', tags: ['Cocktail method'], summary: 'Show a single method', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\CocktailMethod::class),
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

    #[OAT\Post(path: '/cocktail-methods', tags: ['Cocktail method'], summary: 'Create a new method', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CocktailMethodRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\CocktailMethod::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(CocktailMethodRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', CocktailMethod::class)) {
            abort(403);
        }

        $method = new CocktailMethod();
        $method->name = $request->post('name');
        $method->dilution_percentage = (int) $request->post('dilution_percentage');
        $method->bar_id = bar()->id;
        $method->save();

        return (new CocktailMethodResource($method))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktail-methods.show', $method->id));
    }

    #[OAT\Put(path: '/cocktail-methods/{id}', tags: ['Cocktail method'], summary: 'Update a specific method', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\CocktailMethodRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\CocktailMethod::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(CocktailMethodRequest $request, int $id): JsonResource
    {
        $method = CocktailMethod::findOrFail($id);

        if ($request->user()->cannot('edit', $method)) {
            abort(403);
        }

        $method->name = $request->post('name');
        $method->dilution_percentage = (int) $request->post('dilution_percentage');
        $method->updated_at = now();
        $method->save();

        return new CocktailMethodResource($method);
    }

    #[OAT\Delete(path: '/cocktail-methods/{id}', tags: ['Cocktail method'], summary: 'Delete specific method', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $method = CocktailMethod::findOrFail($id);

        if ($request->user()->cannot('delete', $method)) {
            abort(403);
        }

        $method->delete();

        return new Response(null, 204);
    }
}
