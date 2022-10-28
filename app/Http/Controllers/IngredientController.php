<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientCategory;
use Kami\Cocktail\Services\IngredientService;
use Kami\Cocktail\Http\Resources\ErrorResource;
use Kami\Cocktail\Http\Requests\IngredientRequest;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Kami\Cocktail\Http\Resources\IngredientCategoryResource;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $ingredients = Ingredient::with('category', 'images')
            ->orderBy('name')
            ->orderBy('ingredient_category_id')
            ->withCount('cocktails');

        if ($request->has('category_id')) {
            $ingredients->where('ingredient_category_id', $request->get('category_id'));
        }

        if ($request->has('on_shopping_list')) {
            $usersList = $request->user()->shoppingLists->pluck('ingredient_id');
            $ingredients->whereIn('id', $usersList);
        }

        return IngredientResource::collection($ingredients->get());
    }

    public function show(int|string $id)
    {
        try {
            $ingredient = Ingredient::with('cocktails', 'images')
                ->where('id', $id)
                ->orWhere('slug', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return (new ErrorResource($e))->response()->setStatusCode(404);
        } catch (Throwable $e) {
            return (new ErrorResource($e))->response()->setStatusCode(400);
        }

        return new IngredientResource($ingredient);
    }

    public function store(IngredientService $ingredientService, IngredientRequest $request)
    {
        $ingredient = $ingredientService->createIngredient(
            $request->post('name'),
            (int) $request->post('ingredient_category_id'),
            floatval($request->post('strength', '0')),
            $request->post('description'),
            $request->post('origin'),
            $request->post('color'),
            $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null,
            $request->post('images', [])
        );

        return new IngredientResource($ingredient);
    }

    public function update(IngredientService $ingredientService, IngredientRequest $request, int $id)
    {
        $ingredient = $ingredientService->updateIngredient(
            $id,
            $request->post('name'),
            (int) $request->post('ingredient_category_id'),
            floatval($request->post('strength', '0')),
            $request->post('description'),
            $request->post('origin'),
            $request->post('color'),
            $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null,
            $request->post('images', [])
        );

        return new IngredientResource($ingredient);
    }

    public function delete(int $id)
    {
        try {
            Ingredient::findOrFail($id)->delete();
        } catch (ModelNotFoundException $e) {
            return (new ErrorResource($e))->response()->setStatusCode(404);
        } catch (Throwable $e) {
            return (new ErrorResource($e))->response()->setStatusCode(400);
        }

        return new SuccessActionResource((object) ['id' => $id]);
    }

    public function categories()
    {
        // TODO MOVE
        $categories = IngredientCategory::all();

        return IngredientCategoryResource::collection($categories);
    }
}
