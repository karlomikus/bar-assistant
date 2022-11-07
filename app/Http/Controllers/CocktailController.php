<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Http\Requests\CocktailRequest;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;

class CocktailController extends Controller
{
    /**
     * List all cocktails
     * - Paginated by 15 items
     * Optional query strings:
     * - user_id -> Filter by user id
     */
    public function index(Request $request)
    {
        $cocktails = Cocktail::with('ingredients.ingredient', 'images', 'tags');

        if ($request->has('user_id')) {
            $cocktails->where('user_id', $request->get('user_id'));
        }

        return CocktailResource::collection($cocktails->paginate(15));
    }

    /**
     * Return a single random cocktail
     */
    public function random()
    {
        $cocktail = Cocktail::inRandomOrder()
            ->firstOrFail()
            ->load('ingredients.ingredient', 'images', 'tags');

        return new CocktailResource($cocktail);
    }

    /**
     * Show a single cocktail by it's id or URL slug
     */
    public function show(int|string $idOrSlug)
    {
        $cocktail = Cocktail::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->firstOrFail()
            ->load('ingredients.ingredient', 'images', 'tags', 'glass');

        return new CocktailResource($cocktail);
    }

    /**
     * Create a new cocktail
     */
    public function store(CocktailService $cocktailService, CocktailRequest $request): JsonResponse
    {
        try {
            $cocktail = $cocktailService->createCocktail(
                $request->post('name'),
                $request->post('instructions'),
                $request->post('ingredients'),
                $request->user()->id,
                $request->post('description'),
                $request->post('garnish'),
                $request->post('source'),
                $request->post('images', []),
                $request->post('tags', []),
                $request->post('glass_id'),
            );
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    /**
     * Update a single cocktail by id
     */
    public function update(CocktailService $cocktailService, CocktailRequest $request, int $id): JsonResponse
    {
        try {
            $cocktail = $cocktailService->updateCocktail(
                $id,
                $request->post('name'),
                $request->post('instructions'),
                $request->post('ingredients'),
                $request->user()->id,
                $request->post('description'),
                $request->post('garnish'),
                $request->post('source'),
                $request->post('images', []),
                $request->post('tags', []),
                $request->post('glass_id'),
            );
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }

        return (new CocktailResource($cocktail))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    /**
     * Delete a single cocktail by id
     */
    public function delete(int $id)
    {
        Cocktail::findOrFail($id)->delete();

        return new SuccessActionResource((object) ['id' => $id]);
    }

    /**
     * Show all cocktails that current user can make with
     * the ingredients he added to his shelf
     */
    public function userShelf(CocktailService $cocktailService, Request $request)
    {
        $cocktails = $cocktailService->getCocktailsByUserIngredients($request->user()->id)
            ->load('ingredients.ingredient', 'images', 'tags');

        return CocktailResource::collection($cocktails);
    }

    /**
     * Favorite a cocktail by id
     */
    public function favorite(CocktailService $cocktailService, Request $request, int $id)
    {
        $isFavorite = $cocktailService->toggleFavorite($request->user(), $id);

        return new SuccessActionResource((object) ['id' => $id, 'is_favorited' => $isFavorite]);
    }

    /**
     * Show all cocktails that current user added to his favorites
     */
    public function userFavorites(Request $request)
    {
        $cocktails = $request->user()->favorites->pluck('cocktail');

        return CocktailResource::collection($cocktails);
    }
}
