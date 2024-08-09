<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\RatingRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\RatingResource;

class RatingController extends Controller
{
    #[OAT\Post(path: '/ratings/cocktails/{id}', tags: ['Ratings'], summary: 'Rate a cocktail', parameters: [
        new BAO\Parameters\BarIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'rating', type: 'integer'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Rating::class),
    ])]
    #[BAO\NotAuthorizedResponse]
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

    #[OAT\Delete(path: '/ratings/cocktails/{id}', tags: ['Ratings'], summary: 'Delete current user\'s cocktail rating', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
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
