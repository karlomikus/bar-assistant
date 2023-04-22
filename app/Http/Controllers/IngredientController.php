<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientRequest;
use Kami\Cocktail\Http\Resources\IngredientResource;

class IngredientController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $ingredients = Ingredient::with('category', 'images')
            ->orderBy('name')
            ->orderBy('ingredient_category_id')
            ->withCount('cocktails')
            ->limit($request->get('limit', null));

        if ($request->has('category_id')) {
            $ingredients->where('ingredient_category_id', $request->get('category_id'));
        }

        if ($request->has('on_shopping_list')) {
            $usersList = $request->user()->shoppingLists->pluck('ingredient_id');
            $ingredients->whereIn('id', $usersList);
        }

        if ($request->has('on_shelf')) {
            $ingredients->join('user_ingredients', 'user_ingredients.ingredient_id', '=', 'ingredients.id')->where('user_ingredients.user_id', $request->user()->id);
        }

        return IngredientResource::collection($ingredients->get());
    }

    public function show(int|string $id): JsonResource
    {
        $ingredient = Ingredient::with('cocktails', 'images', 'varieties', 'parentIngredient')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        return new IngredientResource($ingredient);
    }

    public function store(IngredientService $ingredientService, IngredientRequest $request): JsonResponse
    {
        $ingredient = $ingredientService->createIngredient(
            $request->post('name'),
            (int) $request->post('ingredient_category_id'),
            auth()->user()->id,
            floatval($request->post('strength', '0')),
            $request->post('description'),
            $request->post('origin'),
            $request->post('color'),
            $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null,
            $request->post('images', [])
        );

        return (new IngredientResource($ingredient))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('ingredients.show', $ingredient->id));
    }

    public function update(IngredientService $ingredientService, IngredientRequest $request, int $id): JsonResource
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('edit', $ingredient)) {
            abort(403);
        }

        $ingredient = $ingredientService->updateIngredient(
            $id,
            $request->post('name'),
            (int) $request->post('ingredient_category_id'),
            auth()->user()->id,
            floatval($request->post('strength', '0')),
            $request->post('description'),
            $request->post('origin'),
            $request->post('color'),
            $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null,
            $request->post('images', [])
        );

        return new IngredientResource($ingredient);
    }

    public function delete(Request $request, int $id): Response
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('delete', $ingredient)) {
            abort(403);
        }

        $ingredient->delete();

        return response(null, 204);
    }

    public function find(Request $request): JsonResource
    {
        $name = $request->get('name');

        $ingredient = Ingredient::with('cocktails', 'images', 'varieties', 'parentIngredient')->whereRaw('lower(name) = ?', [strtolower($name)])->firstOrFail();

        return new IngredientResource($ingredient);
    }
}
