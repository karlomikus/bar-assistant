<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Review\ReviewId;
use Kami\Cocktail\Models\CocktailReview;
use Kami\Cocktail\Http\Requests\ReviewRequest;
use BarAssistant\Domain\Review\ReviewRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ReviewResource;
use BarAssistant\Application\Review\ReviewService;
use BarAssistant\Application\Review\DTO\CreateReviewRequest;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ReviewRepository $reviewRepository,
    ) {
    }

    #[OAT\Get(path: '/cocktails/{id}/reviews', tags: ['Reviews'], operationId: 'listCocktailReviews', description: 'List reviews for a single cocktail', summary: 'List cocktail reviews', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(ReviewResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function index(Request $request, int $id): JsonResource
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('review', $cocktail)) {
            abort(403);
        }

        $perPage = (int) $request->query('per_page', '15');

        $reviews = CocktailReview::queryReviewsForCocktail($id)->paginate(perPage: $perPage);

        return ReviewResource::collection($reviews);
    }

    #[OAT\Post(path: '/cocktails/{id}/reviews', tags: ['Reviews'], operationId: 'saveCocktailReview', description: 'Create a review for a single cocktail', summary: 'Create cocktail review', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\ReviewRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function store(ReviewRequest $request, int $id): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        if ($request->user()->cannot('review', $cocktail)) {
            abort(403);
        }

        $barMembership = $request->user()->getBarMembership((int) $cocktail->bar_id);
        if ($barMembership === null) {
            abort(403);
        }

        try {
            $this->reviewService->createReview(new CreateReviewRequest(
                barMembershipId: $barMembership->id,
                cocktailId: $cocktail->id,
                content: $request->input('content'),
            ));
        } catch (\BarAssistant\Application\Review\Exception\ReviewAlreadyExistsException) {
            abort(409, 'A review for this cocktail already exists.');
        }

        return new Response();
    }

    #[OAT\Delete(path: '/cocktails/{id}/reviews/{reviewId}', tags: ['Reviews'], operationId: 'deleteCocktailReview', description: 'Delete a review for a single cocktail', summary: 'Delete cocktail review', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new OAT\Parameter(name: 'reviewId', in: 'path', description: 'Database id of a review', required: true, schema: new OAT\Schema(type: 'integer')),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function destroy(Request $request, int $id, int $reviewId): Response
    {
        $cocktail = Cocktail::findOrFail($id);

        $review = $this->reviewRepository->findById(new ReviewId($reviewId));
        if ($review === null) {
            abort(404);
        }

        if ($request->user()->cannot('delete', [$review, $cocktail])) {
            abort(403);
        }

        $this->reviewService->deleteReview($review);

        return new Response(null, 204);
    }
}
