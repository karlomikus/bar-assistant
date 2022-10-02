<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Services\CocktailService;
use Kami\Cocktail\Http\Resources\CocktailResource;

class CocktailController extends Controller
{
    public function index()
    {
        $cocktails = Cocktail::paginate(30);

        return CocktailResource::collection($cocktails);
    }

    public function store(CocktailService $cocktailService, Request $request)
    {
        $cocktail = $cocktailService->createCocktail(
            $request->post('name'),
            $request->post('instructions'),
            $request->post('ingredients'),
            $request->post('description'),
            $request->post('source'),
            $request->post('image'),
            $request->post('tags'),
        );

        return new CocktailResource($cocktail);
    }
}
