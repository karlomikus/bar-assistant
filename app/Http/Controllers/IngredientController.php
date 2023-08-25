<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Services\IngredientService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\IngredientRequest;
use Kami\Cocktail\Http\Resources\IngredientResource;
use Kami\Cocktail\Http\Filters\IngredientQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;

class IngredientController extends Controller
{
    public function index(IngredientService $ingredientService, Request $request): JsonResource
    {
        try {
            $ingredients = (new IngredientQueryFilter($ingredientService))->paginate($request->get('per_page', 50));
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        return IngredientResource::collection($ingredients);
    }

    public function show(Request $request, int|string $id): JsonResource
    {
        $ingredient = Ingredient::with('cocktails', 'images', 'varieties', 'parentIngredient')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        return new IngredientResource($ingredient);
    }

    public function store(IngredientService $ingredientService, IngredientRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', Ingredient::class)) {
            abort(403);
        }

        $ingredient = $ingredientService->createIngredient(
            bar()->id,
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

    public function extra(Request $request, CocktailService $cocktailService, int $id): JsonResponse
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('show', $ingredient)) {
            abort(403);
        }

        $currentShelfIngredients = $request->user()->getShelfIngredients($ingredient->bar_id)->pluck('ingredient_id');
        $currentShelfCocktails = $cocktailService->getCocktailsByIngredients($currentShelfIngredients->toArray())->values();
        $extraShelfCocktails = $cocktailService->getCocktailsByIngredients($currentShelfIngredients->push($ingredient->id)->toArray())->values();

        if ($currentShelfCocktails->count() === $extraShelfCocktails->count()) {
            return response()->json(['data' => []]);
        }

        $extraCocktails = Cocktail::whereIn('id', $extraShelfCocktails->diff($currentShelfCocktails)->values())->where('bar_id', '=', $ingredient->bar_id)->get();

        return response()->json([
            'data' => $extraCocktails->map(function (Cocktail $cocktail) {
                return [
                    'id' => $cocktail->id,
                    'slug' => $cocktail->slug,
                    'name' => $cocktail->name,
                ];
            })
        ]);
    }
}
