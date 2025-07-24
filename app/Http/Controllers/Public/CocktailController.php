<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers\Public;

use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Controllers\Controller;
use Kami\Cocktail\Http\Resources\ExploreCocktailResource;

class CocktailController extends Controller
{
    /**
     * @return array<string>
     */
    public function index(string $barSlug): array
    {
        return [$barSlug];
    }

    public function show(string $barSlug, string $id): ExploreCocktailResource
    {
        $cocktail = Cocktail::where('public_id', $id)->firstOrFail()->load('ingredients.ingredient');

        return new ExploreCocktailResource($cocktail);
    }
}
