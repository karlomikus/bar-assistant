<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\RatingRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\RatingResource;

class RatingController extends Controller
{
    public function rateCocktail(RatingRequest $request, int $cocktailId): JsonResource
    {
        $cocktail = Cocktail::findOrFail($cocktailId);

        if ($cocktail->getUserRating($request->user()->id) !== null) {
            abort(400, 'Rating for this resource already exists.');
        }

        $rating = $cocktail->rate(
            (int) $request->post('rating'),
            $request->user()->id
        );

        return new RatingResource($rating);
    }
}
