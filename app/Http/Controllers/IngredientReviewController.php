<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Kami\Cocktail\Models\Ingredient;
use Kami\Cocktail\Models\IngredientReview;
use BarAssistant\Domain\Review\Recommendation;
use Illuminate\Http\Resources\Json\JsonResource;
use BarAssistant\Domain\Review\IngredientReviewId;
use Kami\Cocktail\Http\Requests\IngredientReviewRequest;
use BarAssistant\Domain\Review\IngredientReviewRepository;
use Kami\Cocktail\Http\Resources\IngredientReviewResource;
use BarAssistant\Application\Review\IngredientReviewService;
use BarAssistant\Application\Review\DTO\CreateIngredientReviewRequest;
use BarAssistant\Application\Review\DTO\UpdateIngredientReviewRequest;
use BarAssistant\Application\Review\Exception\IngredientReviewAlreadyExistsException;

class IngredientReviewController extends Controller
{
    public function __construct(
        private readonly IngredientReviewService $ingredientReviewService,
        private readonly IngredientReviewRepository $ingredientReviewRepository,
    ) {
    }

    #[OAT\Get(path: '/ingredients/{id}/reviews', tags: ['Ingredient Reviews'], operationId: 'listIngredientReviews', description: 'List reviews for a single ingredient', summary: 'List ingredient reviews', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new BAO\Parameters\PageParameter(),
        new BAO\Parameters\PerPageParameter(),
    ])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\PaginateData(IngredientReviewResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function index(Request $request, int $id): JsonResource
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('review', $ingredient)) {
            abort(403);
        }

        $perPage = (int) $request->query('per_page', '15');

        $reviews = IngredientReview::queryReviewsForIngredient($id)->paginate(perPage: $perPage);

        return IngredientReviewResource::collection($reviews);
    }

    #[OAT\Post(path: '/ingredients/{id}/reviews', tags: ['Ingredient Reviews'], operationId: 'saveIngredientReview', description: 'Create a review for a single ingredient', summary: 'Create ingredient review', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientReviewRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function store(IngredientReviewRequest $request, int $id): Response
    {
        $ingredient = Ingredient::findOrFail($id);

        if ($request->user()->cannot('review', $ingredient)) {
            abort(403);
        }

        $barMembership = $request->user()->getBarMembership((int) $ingredient->bar_id);
        if ($barMembership === null) {
            abort(403);
        }

        try {
            $this->ingredientReviewService->createReview(new CreateIngredientReviewRequest(
                barMembershipId: $barMembership->id,
                ingredientId: $ingredient->id,
                content: (string) $request->input('content'),
                recommendation: $request->filled('recommendation') ? Recommendation::from((string) $request->input('recommendation')) : null,
                tasteDescriptors: $this->tasteDescriptors($request),
            ));
        } catch (IngredientReviewAlreadyExistsException) {
            abort(409, 'A review for this ingredient already exists.');
        }

        return new Response(null, 201);
    }

    #[OAT\Put(path: '/ingredients/{id}/reviews/{reviewId}', tags: ['Ingredient Reviews'], operationId: 'updateIngredientReview', description: 'Update a review for a single ingredient', summary: 'Update ingredient review', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new OAT\Parameter(name: 'reviewId', in: 'path', description: 'Database id of a review', required: true, schema: new OAT\Schema(type: 'integer')),
    ], requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\IngredientReviewRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(IngredientReviewResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function update(IngredientReviewRequest $request, int $id, int $reviewId): JsonResource
    {
        $ingredient = Ingredient::findOrFail($id);

        $review = $this->ingredientReviewRepository->findById(new IngredientReviewId($reviewId));
        if ($review === null) {
            abort(404);
        }

        if ($request->user()->cannot('update', [$review, $ingredient])) {
            abort(403);
        }

        $this->ingredientReviewService->updateReview($review, new UpdateIngredientReviewRequest(
            reviewId: $reviewId,
            content: (string) $request->input('content'),
            recommendation: $request->filled('recommendation') ? Recommendation::from((string) $request->input('recommendation')) : null,
            tasteDescriptors: $this->tasteDescriptors($request),
        ));

        $updated = IngredientReview::queryReviewsForIngredient($id)->where('id', $reviewId)->firstOrFail();

        return new IngredientReviewResource($updated);
    }

    #[OAT\Delete(path: '/ingredients/{id}/reviews/{reviewId}', tags: ['Ingredient Reviews'], operationId: 'deleteIngredientReview', description: 'Delete a review for a single ingredient', summary: 'Delete ingredient review', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
        new OAT\Parameter(name: 'reviewId', in: 'path', description: 'Database id of a review', required: true, schema: new OAT\Schema(type: 'integer')),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function destroy(Request $request, int $id, int $reviewId): Response
    {
        $ingredient = Ingredient::findOrFail($id);

        $review = $this->ingredientReviewRepository->findById(new IngredientReviewId($reviewId));
        if ($review === null) {
            abort(404);
        }

        if ($request->user()->cannot('delete', [$review, $ingredient])) {
            abort(403);
        }

        $this->ingredientReviewService->deleteReview($review);

        return new Response(null, 204);
    }

    /**
     * @return string[]
     */
    private function tasteDescriptors(IngredientReviewRequest $request): array
    {
        $descriptors = $request->input('taste_descriptors', []);

        return is_array($descriptors) ? array_values(array_map('strval', $descriptors)) : [];
    }
}
