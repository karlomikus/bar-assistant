<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\CocktailMethodRequest;
use Kami\Cocktail\Http\Resources\CocktailMethodResource;

class CocktailMethodController extends Controller
{
    public function index(): JsonResource
    {
        $methods = CocktailMethod::orderBy('id')->withCount('cocktails')->get();

        return CocktailMethodResource::collection($methods);
    }

    public function show(int $id): JsonResource
    {
        $method = CocktailMethod::withCount('cocktails')->findOrFail($id);

        return new CocktailMethodResource($method);
    }

    public function store(CocktailMethodRequest $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $method = new CocktailMethod();
        $method->name = $request->post('name');
        $method->dilution_percentage = (int) $request->post('dilution_percentage');
        $method->save();

        return (new CocktailMethodResource($method))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('cocktail-methods.show', $method->id));
    }

    public function update(CocktailMethodRequest $request, int $id): JsonResource
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $method = CocktailMethod::findOrFail($id);
        $method->name = $request->post('name');
        $method->dilution_percentage = (int) $request->post('dilution_percentage');
        $method->save();

        // TODO: Update index abv

        return new CocktailMethodResource($method);
    }

    public function delete(Request $request, int $id): Response
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        CocktailMethod::findOrFail($id)->delete();

        return response(null, 204);
    }
}
