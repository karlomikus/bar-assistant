<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\PriceCategory;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\PriceCategoryRequest;
use Kami\Cocktail\Http\Resources\PriceCategoryResource;
use BarAssistant\Application\Ingredient\PriceCategoryService;
use BarAssistant\Application\Ingredient\DTO\CreatePriceCategoryRequest;
use BarAssistant\Application\Ingredient\DTO\UpdatePriceCategoryRequest;

class PriceCategoryController extends Controller
{
    #[OAT\Get(path: '/price-categories', tags: ['Price category'], operationId: 'listPriceCategories', description: 'List all price categories in a bar', summary: 'List price categories', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(PriceCategoryResource::class),
    ])]
    public function index(): JsonResource
    {
        $priceCategories = PriceCategory::orderBy('name')->filterByBar()->get();

        return PriceCategoryResource::collection($priceCategories);
    }

    #[OAT\Get(path: '/price-categories/{id}', tags: ['Price category'], description: 'Show a single price category', summary: 'Show price category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(PriceCategoryResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $priceCategory = PriceCategory::findOrFail($id);
        $user = $request->user();

        if ($user === null || $user->cannot('show', $priceCategory)) {
            abort(403);
        }

        return new PriceCategoryResource($priceCategory);
    }

    #[OAT\Post(path: '/price-categories', tags: ['Price category'], operationId: 'savePriceCategory', description: 'Create a new price category', summary: 'Create price category', parameters: [
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\PriceCategoryRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(PriceCategoryService $service, PriceCategoryRequest $request): Response
    {
        $user = $request->user();
        if ($user === null || $user->cannot('create', PriceCategory::class)) {
            abort(403);
        }

        $priceCategoryRequest = BAO\Schemas\PriceCategoryRequest::fromLaravelRequest($request);

        $priceCategoryResult = $service->createPriceCategory(new CreatePriceCategoryRequest(
            barId: bar()->id,
            name: $priceCategoryRequest->name,
            currency: $priceCategoryRequest->currency,
            description: $priceCategoryRequest->description,
        ));

        return new Response(status: 201, headers: ['Location' => route('price-categories.show', $priceCategoryResult->id, false)]);
    }

    #[OAT\Put(path: '/price-categories/{id}', tags: ['Price category'], operationId: 'updatePriceCategory', description: 'Update a single price category', summary: 'Update price category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\PriceCategoryRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(int $id, PriceCategoryService $service, PriceCategoryRequest $request): Response
    {
        $priceCategory = PriceCategory::findOrFail($id);
        $user = $request->user();

        if ($user === null || $user->cannot('edit', $priceCategory)) {
            abort(403);
        }

        $priceCategoryRequest = BAO\Schemas\PriceCategoryRequest::fromLaravelRequest($request);

        $service->updatePriceCategory(new UpdatePriceCategoryRequest(
            priceCategoryId: $id,
            name: $priceCategoryRequest->name,
            currency: $priceCategoryRequest->currency,
            description: $priceCategoryRequest->description,
        ));

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/price-categories/{id}', tags: ['Price category'], operationId: 'deletePriceCategory', description: 'Delete a single price category', summary: 'Delete price category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $priceCategory = PriceCategory::findOrFail($id);
        $user = $request->user();

        if ($user === null || $user->cannot('delete', $priceCategory)) {
            abort(403);
        }

        $priceCategory->delete();

        return new Response(null, 204);
    }
}
