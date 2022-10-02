<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Services\CocktailService;

class CocktailController extends Controller
{
    public function store(CocktailService $cocktailService, Request $request): JsonResponse
    {
        $cocktail = $cocktailService->createCocktail(
            $request->post('name'),
            $request->post('instructions'),
            $request->post('ingredients'),
            $request->post('description'),
            $request->post('source'),
            $request->post('image'),
        );

        return response()->json($cocktail);
    }
}
