<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Http\Resources\CocktailResource;
use Kami\Cocktail\Http\Resources\SuccessActionResource;
use Kami\Cocktail\Http\Resources\ErrorResource;

class CocktailController extends Controller
{
    public function index()
    {
        $cocktails = Cocktail::with('ingredients.ingredient', 'images', 'tags')->paginate(15);

        return CocktailResource::collection($cocktails);
    }

    public function show(int $id)
    {
        try {
            $cocktail = Cocktail::findOrFail($id)->load('ingredients.ingredient', 'images', 'tags');
        } catch (Throwable $e) {
            return new ErrorResource($e);
        }

        return new CocktailResource($cocktail);
    }

    public function store(CocktailService $cocktailService, Request $request): JsonResponse
    {
        $cocktail = $cocktailService->createCocktail(
            $request->post('name'),
            $request->post('instructions'),
            $request->post('ingredients'),
            $request->user()->id,
            $request->post('description'),
            $request->post('garnish'),
            $request->post('source'),
            $request->post('image'),
            $request->post('tags'),
        );

        return (new CocktailResource($cocktail))
            ->response()
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    public function update(CocktailService $cocktailService, Request $request, int $id): JsonResponse
    {
        $cocktail = $cocktailService->updateCocktail(
            $id,
            $request->post('name'),
            $request->post('instructions'),
            $request->post('ingredients'),
            $request->user()->id,
            $request->post('description'),
            $request->post('garnish'),
            $request->post('source'),
            $request->post('image'),
            $request->post('tags'),
        );

        return (new CocktailResource($cocktail))
            ->response()
            ->header('Location', route('cocktails.show', $cocktail->id));
    }

    public function delete(int $id)
    {
        try {
            Cocktail::findOrFail($id)->delete();
        } catch (Throwable $e) {
            return new ErrorResource($e);
        }

        return new SuccessActionResource((object) ['id' => $id]);
    }

    public function userShelf(CocktailService $cocktailService, Request $request)
    {
        $cocktails = $cocktailService->getCocktailsByUserIngredients($request->user()->id)
            ->load('ingredients.ingredient', 'images', 'tags');

        return CocktailResource::collection($cocktails);
    }

    public function favorite(CocktailService $cocktailService, Request $request, int $id)
    {
        $isFavorite = $cocktailService->toggleFavorite($request->user(), $id);

        return new SuccessActionResource((object) ['id' => $id, 'is_favorited' => $isFavorite]);
    }

    public function userFavorites(Request $request)
    {
        $cocktails = $request->user()->favorites->pluck('cocktail');

        return CocktailResource::collection($cocktails);
    }
}
