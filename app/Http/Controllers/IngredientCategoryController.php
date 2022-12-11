<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

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
        $categories = IngredientCategory::orderBy('name')->get();

        return IngredientCategoryResource::collection($categories);
    }

    public function show(int $id): JsonResource
    {
        $category = IngredientCategory::findOrFail($id);

        return new IngredientCategoryResource($category);
    }

    public function store(IngredientCategoryRequest $request): JsonResponse
    {
        $category = new IngredientCategory();
        $category->name = $request->post('name');
        $category->description = $request->post('description');
        $category->save();

        return (new IngredientCategoryResource($category))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('ingredient-categories.show', $category->id));
    }

    public function update(IngredientCategoryRequest $request, int $id): JsonResource
    {
        $category = IngredientCategory::findOrFail($id);
        $category->name = $request->post('name');
        $category->description = $request->post('description');
        $category->save();

        return new IngredientCategoryResource($category);
    }

    public function delete(int $id): Response
    {
        IngredientCategory::findOrFail($id)->delete();

        return response(null, 204);
    }
}
