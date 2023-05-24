<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\CocktailService;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Filters\CocktailQueryFilter;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Http\Resources\CocktailPublicResource;
use Kami\Cocktail\DataObjects\Cocktail\Ingredient as IngredientDataObject;

class CocktailController extends Controller
{
    /**
     * List all cocktails
     */
    public function index(CocktailService $cocktailService, Request $request): JsonResource
    {
        try {
            $cocktails = new CocktailQueryFilter();
        } catch (InvalidFilterQuery $e) {
            abort(400, $e->getMessage());
        }

        $cocktails = $cocktails->paginate($request->get('per_page', 15));

        // Append ratings
        $averageRatings = $cocktailService->getCocktailAvgRatings();
        $userRatings = $cocktailService->getCocktailUserRatings($request->user()->id);
        $cocktails->getCollection()->map(function (Cocktail $cocktail) use ($averageRatings, $userRatings) {
            $cocktail
                ->setAverageRating($averageRatings[$cocktail->id] ?? 0.0)
                ->setUserRating($userRatings[$cocktail->id] ?? null);

            return $cocktail;
        });

        return CocktailResource::collection($cocktails);
    }

    /**
     * Return a single random cocktail
     */
    public function random(): JsonResource
    {
        $cocktail = Cocktail::inRandomOrder()
            ->firstOrFail()
            ->load('ingredients.ingredient', 'images', 'tags', 'method');

        return new CocktailResource($cocktail);
    }

    /**
     * Show a single cocktail by it's id or URL slug
     */
    public function show(int|string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail()
            ->load(['ingredients.ingredient', 'images' => function ($query) {
                $query->orderBy('sort');
            }, 'tags', 'glass', 'ingredients.substitutes', 'method', 'notes']);

        return new CocktailResource($cocktail);
    }

    /**
     * Create a new cocktail
     */
    public function store(CocktailService $cocktailService, CocktailRequest $request): JsonResponse
    {
        $ingredients = [];
        foreach ($request->post('ingredients') as $formIngredient) {
            $ingredient = new IngredientDataObject(
                (int) $formIngredient['ingredient_id'],
                '',
                (float) $formIngredient['amount'],
                $formIngredient['units'],
                (int) $formIngredient['sort'],
                $formIngredient['optional'] ?? false,
                $formIngredient['substitutes'] ?? [],
            );
            $ingredients[] = $ingredient;
        }

        try {
            $cocktail = $cocktailService->createCocktail(
                $request->post('name'),
                $request->post('instructions'),
                $ingredients,
                $request->user()->id,
                $request->post('description'),
                $request->post('garnish'),
                $request->post('source'),
                $request->post('images', []),
                $request->post('tags', []),
                $request->post('glass_id') ? (int) $request->post('glass_id') : null,
                $request->post('cocktail_method_id') ? (int) $request->post('cocktail_method_id') : null,
            );
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load('ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes');

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    /**
     * Update a single cocktail by id
     */
    public function update(CocktailService $cocktailService, CocktailRequest $request, int $id): JsonResource
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('edit', $cocktail)) {
            abort(403);
        }

        $ingredients = [];
        foreach ($request->post('ingredients') as $formIngredient) {
            $ingredient = new IngredientDataObject(
                (int) $formIngredient['ingredient_id'],
                '',
                (float) $formIngredient['amount'],
                $formIngredient['units'],
                (int) $formIngredient['sort'],
                $formIngredient['optional'] ?? false,
                $formIngredient['substitutes'] ?? [],
            );
            $ingredients[] = $ingredient;
        }

        try {
            $cocktail = $cocktailService->updateCocktail(
                $id,
                $request->post('name'),
                $request->post('instructions'),
                $ingredients,
                $request->user()->id,
                $request->post('description'),
                $request->post('garnish'),
                $request->post('source'),
                $request->post('images', []),
                $request->post('tags', []),
                $request->post('glass_id') ? (int) $request->post('glass_id') : null,
                $request->post('cocktail_method_id') ? (int) $request->post('cocktail_method_id') : null,
            );
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        $cocktail->load('ingredients.ingredient', 'images', 'tags', 'glass', 'ingredients.substitutes');

        return new CocktailResource($cocktail);
    }

    /**
     * Delete a single cocktail by id
     */
    public function delete(Request $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('delete', $cocktail)) {
            abort(403);
        }

        $cocktail->delete();

        return response(null, 204);
    }

    /**
     * Show all cocktails that current user can make with
     * the ingredients he added to his shelf
     */
    public function userShelf(CocktailService $cocktailService, Request $request): JsonResource|JsonResponse
    {
        $limit = $request->has('limit') ? (int) $request->get('limit') : null;

        $cocktailIds = $cocktailService->getCocktailsByUserIngredients($request->user()->id, $limit);

        if ($request->has('format')) {
            return response()->json([
                'data' => $cocktailIds
            ]);
        }

        $averageRatings = $cocktailService->getCocktailAvgRatings();
        $userRatings = $cocktailService->getCocktailUserRatings($request->user()->id);

        $cocktails = Cocktail::orderBy('name')->find($cocktailIds)
            ->load('ingredients.ingredient', 'images', 'tags', 'method')
            ->map(function ($cocktail) use ($averageRatings, $userRatings) {
                $cocktail
                    ->setAverageRating($averageRatings[$cocktail->id] ?? 0.0)
                    ->setUserRating($userRatings[$cocktail->id] ?? null);

                return $cocktail;
            });

        return CocktailResource::collection($cocktails);
    }

    /**
     * Favorite a cocktail by id
     */
    public function toggleFavorite(CocktailService $cocktailService, Request $request, int $id): JsonResource
    {
        $isFavorite = $cocktailService->toggleFavorite($request->user(), $id);

        return new SuccessActionResource((object) ['id' => $id, 'is_favorited' => $isFavorite]);
    }

    public function makePublic(int|string $idOrSlug): JsonResource
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        if ($cocktail->public_id) {
            return new CocktailPublicResource($cocktail);
        }

        $cocktail = $cocktail->makePublic(now());

        return new CocktailPublicResource($cocktail);
    }

    public function makePrivate(int|string $idOrSlug): Response
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail();

        $cocktail = $cocktail->makePrivate();

        return response(null, 204);
    }
}
