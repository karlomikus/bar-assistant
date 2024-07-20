<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\PriceCategory;
use Kami\Cocktail\Http\Requests\PriceCategoryRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\PriceCategoryResource;

class PriceCategoryController extends Controller
{
    public function index(): JsonResource
    {
        $priceCategories = PriceCategory::orderBy('name')->filterByBar()->get();

        return PriceCategoryResource::collection($priceCategories);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $priceCategory = PriceCategory::findOrFail($id);

        if ($request->user()->cannot('show', $priceCategory)) {
            abort(403);
        }

        return new PriceCategoryResource($priceCategory);
    }

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
