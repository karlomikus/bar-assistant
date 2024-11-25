<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\IngredientCategory;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientCategoryRequest;
use Kami\Cocktail\Http\Resources\IngredientCategoryResource;

class IngredientCategoryController extends Controller
{
    #[OAT\Get(path: '/ingredient-categories', tags: ['Ingredient category'], operationId: 'listIngredientCategories', description: 'List all ingredient categories in a bar', summary: 'List ingredient categories', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapItemsWithData(BAO\Schemas\IngredientCategory::class),
    ])]
    public function index(): JsonResource
    {
        $categories = IngredientCategory::orderBy('name')->withCount('ingredients')->filterByBar()->get();

        return IngredientCategoryResource::collection($categories);
    }

    #[OAT\Get(path: '/ingredient-categories/{id}', tags: ['Ingredient category'], operationId: 'showIngredientCategory', description: 'Show a specific ingredient category', summary: 'Show ingredient category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\IngredientCategory::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function show(Request $request, int $id): JsonResource
    {
        $category = IngredientCategory::findOrFail($id);

        if ($request->user()->cannot('show', $category)) {
            abort(403);
        }

        return new IngredientCategoryResource($category);
    }

    #[OAT\Post(path: '/ingredient-categories', tags: ['Ingredient category'], operationId: 'saveIngredientCategory', description: 'Create a specific ingredient category', summary: 'Create ingredient category', parameters: [
        new BAO\Parameters\BarIdParameter(),
        new BAO\Parameters\BarIdHeaderParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientCategoryRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\IngredientCategory::class),
    ], headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function store(IngredientCategoryRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', IngredientCategory::class)) {
            abort(403);
        }

        $category = new IngredientCategory();
        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->bar_id = bar()->id;
        $category->save();

        return (new IngredientCategoryResource($category))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('ingredient-categories.show', $category->id));
    }

    #[OAT\Put(path: '/ingredient-categories/{id}', tags: ['Ingredient category'], operationId: 'updateIngredientCategory', description: 'Update a specific ingredient category', summary: 'Update ingredient category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientCategoryRequest::class),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\IngredientCategory::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(IngredientCategoryRequest $request, int $id): JsonResource
    {
        $category = IngredientCategory::findOrFail($id);

        if ($request->user()->cannot('edit', $category)) {
            abort(403);
        }

        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->updated_at = now();
        $category->save();

        return new IngredientCategoryResource($category);
    }

    #[OAT\Delete(path: '/ingredient-categories/{id}', tags: ['Ingredient category'], operationId: 'deleteIngredientCategory', description: 'Delete a specific ingredient category', summary: 'Delete ingredient category', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(Request $request, int $id): Response
    {
        $category = IngredientCategory::findOrFail($id);

        if ($request->user()->cannot('delete', $category)) {
            abort(403);
        }

        $category->delete();

        return new Response(null, 204);
    }
}
