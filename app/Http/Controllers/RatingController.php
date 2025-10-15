<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Http\Requests\RatingRequest;

class RatingController extends Controller
{
    #[OAT\Post(path: '/cocktails/{id}/ratings', tags: ['Ratings'], operationId: 'rateCocktail', description: 'Rate a single cocktail', summary: 'Rate cocktail', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'rating', type: 'integer'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotFoundResponse]
    #[BAO\NotAuthorizedResponse]
    public function rateCocktail(RatingRequest $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('rate', $cocktail)) {
            abort(403);
        }

        $cocktail->rate(
            (int) $request->post('rating'),
            $request->user()->id
        );

        if (!empty(config('scout.driver'))) {
            $cocktail->searchable();
        }

        return new Response(null, 204);
    }

    #[OAT\Delete(path: '/cocktails/{id}/ratings', tags: ['Ratings'], operationId: 'deleteRating', description: 'Delete current user cocktail rating', summary: 'Delete cocktail rating', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function deleteCocktailRating(Request $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('rate', $cocktail)) {
            abort(403);
        }

        $cocktail->deleteUserRating($request->user()->id);

        if (!empty(config('scout.driver'))) {
            $cocktail->searchable();
        }

        return new Response(null, 204);
    }
}
