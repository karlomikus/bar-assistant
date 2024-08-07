<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\PriceCategory;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\PriceCategoryRequest;
use Kami\Cocktail\Http\Resources\PriceCategoryResource;

class PriceCategoryController extends Controller
{
    #[OAT\Get(path: '/price-categories', tags: ['Price category'], summary: 'Show a list of price categories', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\PriceCategory::class),
    ])]
    public function index(): JsonResource
    {
        $priceCategories = PriceCategory::orderBy('name')->filterByBar()->get();

        return PriceCategoryResource::collection($priceCategories);
    }

    #[OAT\Get(path: '/price-categories/{id}', tags: ['Price category'], summary: 'Show a price category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\PriceCategory::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $priceCategory = PriceCategory::findOrFail($id);

        if ($request->user()->cannot('show', $priceCategory)) {
            abort(403);
        }

        return new PriceCategoryResource($priceCategory);
    }

    #[OAT\Post(path: '/price-categories', tags: ['Price category'], summary: 'Create a new price category', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\PriceCategoryRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\PriceCategory::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(PriceCategoryRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', PriceCategory::class)) {
            abort(403);
        }

        $priceCategory = new PriceCategory();
        $priceCategory->name = $request->post('name');
        $priceCategory->description = $request->post('description');
        $priceCategory->currency = $request->post('currency');
        $priceCategory->bar_id = bar()->id;
        $priceCategory->save();

        return (new PriceCategoryResource($priceCategory))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('price-categories.show', $priceCategory->id));
    }

    #[OAT\Put(path: '/price-categories/{id}', tags: ['Price category'], summary: 'Update price category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\PriceCategoryRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\PriceCategory::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(int $id, PriceCategoryRequest $request): JsonResource
    {
        $priceCategory = PriceCategory::findOrFail($id);

        if ($request->user()->cannot('edit', $priceCategory)) {
            abort(403);
        }

        $priceCategory->name = $request->post('name');
        $priceCategory->description = $request->post('description');
        $priceCategory->currency = $request->post('currency');
        $priceCategory->save();

        return new PriceCategoryResource($priceCategory);
    }

    #[OAT\Delete(path: '/price-categories/{id}', tags: ['Price category'], summary: 'Delete price category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $priceCategory = PriceCategory::findOrFail($id);

        if ($request->user()->cannot('delete', $priceCategory)) {
            abort(403);
        }

        $priceCategory->delete();

        return response(null, 204);
    }
}
