<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Ingredient;
use BarAssistant\Domain\Rating\RateableType;
use Kami\Cocktail\Http\Requests\RatingRequest;
use BarAssistant\Application\Rating\RatingService;
use BarAssistant\Application\Rating\DTO\RateRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

class IngredientRatingController extends Controller
{
    #[OAT\Post(path: '/ingredients/{id}/ratings', tags: ['Ingredient Ratings'], operationId: 'rateIngredient', description: 'Rate a single ingredient', summary: 'Rate ingredient', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(property: 'rating', type: 'number', description: 'Rating value on a 0.5 step (1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0)'),
            ]),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotFoundResponse]
    #[BAO\NotAuthorizedResponse]
    public function rate(RatingService $ratingService, RatingRequest $request, int $id): Response
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('rate', $ingredient)) {
            abort(403);
        }

        $barMembership = $request->user()->getBarMembership((int) $ingredient->bar_id);
        if ($barMembership === null) {
            abort(403);
        }

        $ratingService->rate(new RateRequest(
            barMembershipId: $barMembership->id,
            rateableId: $ingredient->id,
            type: RateableType::Ingredient,
            value: (float) $request->post('rating'),
        ));

        return new Response(null, 204);
    }

    #[OAT\Delete(path: '/ingredients/{id}/ratings', tags: ['Ingredient Ratings'], operationId: 'deleteIngredientRating', description: 'Delete current user ingredient rating', summary: 'Delete ingredient rating', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function unrate(RatingService $ratingService, Request $request, int $id): Response
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('rate', $ingredient)) {
            abort(403);
        }

        $barMembership = $request->user()->getBarMembership((int) $ingredient->bar_id);
        if ($barMembership === null) {
            abort(403);
        }

        try {
            $ratingService->removeRating($barMembership->id, $ingredient->id, RateableType::Ingredient);
        } catch (EntityNotFoundException) {
            abort(404);
        }

        return new Response(null, 204);
    }
}
