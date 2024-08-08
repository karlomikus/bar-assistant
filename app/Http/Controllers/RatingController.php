<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\RatingRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\RatingResource;

class RatingController extends Controller
{
    public function rateCocktail(RatingRequest $request, int $cocktailId): JsonResource
    {
        $cocktail = Cocktail::findOrFail($cocktailId);

        if ($request->user()->cannot('rate', $cocktail)) {
            abort(403);
        }

        $rating = $cocktail->rate(
            (int) $request->post('rating'),
            $request->user()->id
        );

        $cocktail->searchable();

        return new RatingResource($rating);
    }

    public function deleteCocktailRating(Request $request, int $cocktailId): Response
    {
        $cocktail = Cocktail::findOrFail($cocktailId);

        if ($request->user()->cannot('rate', $cocktail)) {
            abort(403);
        }

        $cocktail->deleteUserRating($request->user()->id);

        $cocktail->searchable();

        return new Response(null, 204);
    }
}
