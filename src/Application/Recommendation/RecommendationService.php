<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Recommendation\RecommendationResult;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Recommendation\RecommendationRepository;
use BarAssistant\Domain\Recommendation\RecommendationScoringService;
use BarAssistant\Application\Recommendation\DTO\RecommendationResultDTO;
use BarAssistant\Application\Recommendation\DTO\GetRecommendationsRequest;

final readonly class RecommendationService
{
    public function __construct(
        private RecommendationRepository $recommendationRepository,
        private RecommendationScoringService $scoringService,
        private MemberRepository $memberRepository,
    ) {
    }

    /**
     * @return RecommendationResultDTO[]
     */
    public function getRecommendations(GetRecommendationsRequest $request): array
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));

        if ($member === null) {
            throw new EntityNotFoundException('Member not found.');
        }

        $barId = new BarId($request->barId);
        $memberId = new MemberId($request->memberId);

        $favoriteCocktailIds = $this->recommendationRepository->getFavoriteCocktailIds($memberId);
        $ratedCocktailIds = $this->recommendationRepository->getRatedCocktailIds($memberId);

        $excludeIds = array_unique(array_merge($favoriteCocktailIds, $ratedCocktailIds));

        $cocktails = $this->recommendationRepository->getCocktailsWithDetails($barId, $excludeIds);

        if (empty($cocktails)) {
            return [];
        }

        $favoriteTags = $this->recommendationRepository->getFavoriteTags($memberId, $barId);
        $negativeTags = $this->recommendationRepository->getNegativeTags($memberId, $barId);
        $favoriteIngredients = $this->recommendationRepository->getFavoriteIngredients($memberId, $barId);
        $barShelfIngredients = $this->recommendationRepository->getBarShelfIngredientIds($barId);

        $results = $this->scoringService->score(
            favoriteTags: $favoriteTags,
            negativeTags: $negativeTags,
            favoriteIngredients: $favoriteIngredients,
            barShelfIngredientIds: $barShelfIngredients,
            cocktails: $cocktails,
        );

        $results = array_slice($results, 0, $request->limit);

        return array_map(
            fn (RecommendationResult $result) => new RecommendationResultDTO(
                cocktailId: $result->cocktailId->value,
                score: $result->score,
                matchedTagIds: $result->matchedTagIds,
                matchedIngredientIds: $result->matchedIngredientIds,
                shelfCompleteness: $result->shelfCompleteness,
            ),
            $results,
        );
    }
}
