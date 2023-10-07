<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientCategoryRequest;
use Kami\Cocktail\Http\Resources\IngredientCategoryResource;

class IngredientCategoryController extends Controller
{
    public function index(): JsonResource
    {
        $categories = IngredientCategory::orderBy('name')->withCount('ingredients')->filterByBar()->get();

        return IngredientCategoryResource::collection($categories);
    }

    public function show(Request $request, int $id): JsonResource
    {
        $category = IngredientCategory::findOrFail($id);

        if ($request->user()->cannot('show', $category)) {
            abort(403);
        }

        return new IngredientCategoryResource($category);
    }

    public function store(IngredientCategoryRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', IngredientCategory::class)) {
            abort(403);
        }

        $category = new IngredientCategory();
        $category->name = $request->post('name');
        $category->description = $request->post('description');
        $category->bar_id = bar()->id;
        $category->save();

        return (new IngredientCategoryResource($category))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('ingredient-categories.show', $category->id));
    }

    public function update(IngredientCategoryRequest $request, int $id): JsonResource
    {
        $category = IngredientCategory::findOrFail($id);

        if ($request->user()->cannot('edit', $category)) {
            abort(403);
        }

        $category->name = $request->post('name');
        $category->description = $request->post('description');
        $category->updated_at = now();
        $category->save();

        return new IngredientCategoryResource($category);
    }

    public function delete(Request $request, int $id): Response
    {
        $category = IngredientCategory::findOrFail($id);

        if ($request->user()->cannot('delete', $category)) {
            abort(403);
        }

        $category->delete();

        return response(null, 204);
    }
}
