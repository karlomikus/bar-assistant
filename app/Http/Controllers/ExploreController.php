<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Kami\Cocktail\Models\Cocktail;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ExploreCocktailResource;

class ExploreController extends Controller
{
    public function cocktail(string $publicId): JsonResource
    {
        $cocktail = Cocktail::where('public_id', $publicId)->firstOrFail()->load('ingredients.ingredient');

        return new ExploreCocktailResource($cocktail);
    }
}
