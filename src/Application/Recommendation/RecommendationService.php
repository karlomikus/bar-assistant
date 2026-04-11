<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Recommendation\RecommendationResult;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Recommendation\RecommendationRepository;
use BarAssistant\Domain\Recommendation\RecommendationScoringService;
use BarAssistant\Application\Recommendation\DTO\RecommendationResultDTO;
use BarAssistant\Application\Recommendation\DTO\GetRecommendationsRequest;
use BarAssistant\Domain\Bar\BarRepository;

final readonly class RecommendationService
{
    public function __construct(
        private RecommendationRepository $recommendationRepository,
        private RecommendationScoringService $scoringService,
        private MemberRepository $memberRepository,
        private BarRepository $barRepository,
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

        $bar = $this->barRepository->findById($member->getBarId());
        if ($bar === null) {
            throw new EntityNotFoundException('Bar not found.');
        }

        $cocktails = $this->recommendationRepository->getApplicableCocktails($member->getId());

        if (empty($cocktails)) {
            return [];
        }

        $favoriteTags = $this->recommendationRepository->getFavoriteTags($member->getId());
        $negativeTags = $this->recommendationRepository->getNegativeTags($member->getId());
        $favoriteIngredients = $this->recommendationRepository->getFavoriteIngredients($member->getId());
        $barShelfIngredients = array_map(static fn ($inventoryItem) => $inventoryItem->ingredientId, $bar->getIngredientInventory());

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
