<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Http\Resources\CocktailResource;

class CocktailController extends Controller
{
    public function index()
    {
        $cocktails = Cocktail::paginate(30)->load('ingredients.ingredient');

        return CocktailResource::collection($cocktails);
    }

    public function show(int $id)
    {
        $cocktail = Cocktail::find($id)->load('ingredients.ingredient');

        return new CocktailResource($cocktail);
    }

    public function store(CocktailService $cocktailService, Request $request): JsonResponse
    {
        $cocktail = $cocktailService->createCocktail(
            $request->post('name'),
            $request->post('instructions'),
            $request->post('ingredients'),
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
}
