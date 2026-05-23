<?php

declare(strict_types=1);

namespace BarAssistant\Application\Recommendation;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Bar\MemberRepository;
use BarAssistant\Domain\Bar\BarInventoryRepository;
use BarAssistant\Domain\Recommendation\RecommendationResult;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Recommendation\RecommendationRepository;
use BarAssistant\Application\Recommendation\DTO\UserTasteProfileDTO;
use BarAssistant\Domain\Recommendation\RecommendationScoringService;
use BarAssistant\Application\Recommendation\DTO\RecommendationResultDTO;
use BarAssistant\Application\Recommendation\DTO\GetRecommendationsRequest;
use BarAssistant\Application\Recommendation\DTO\GetUserTasteProfileRequest;

final readonly class RecommendationService
{
    public function __construct(
        private RecommendationRepository $recommendationRepository,
        private RecommendationScoringService $scoringService,
        private MemberRepository $memberRepository,
        private BarInventoryRepository $barInventoryRepository,
    ) {
    }

    /**
     * @return RecommendationResultDTO[]
     */
    public function getRecommendations(GetRecommendationsRequest $request): array
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));
        if ($member === null || $member->getId() === null) {
            throw new EntityNotFoundException('Member not found.');
        }

        $barInventory = $this->barInventoryRepository->findByBarId($member->getBarId());
        if ($barInventory === null) {
            throw new EntityNotFoundException('Bar not found.');
        }

        $cocktails = $this->recommendationRepository->getApplicableCocktails($member->getId());

        if (empty($cocktails)) {
            return [];
        }

        $favoriteTags = $this->recommendationRepository->getFavoriteTags($member->getId());
        $negativeTags = $this->recommendationRepository->getNegativeTags($member->getId());
        $favoriteIngredients = $this->recommendationRepository->getFavoriteIngredients($member->getId());
        $barShelfIngredients = array_map(static fn ($inventoryItem) => $inventoryItem->ingredientId, $barInventory->getIngredients());

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
            ),
            $results,
        );
    }

    public function getUserTasteProfile(GetUserTasteProfileRequest $request): UserTasteProfileDTO
    {
        $member = $this->memberRepository->findById(new MemberId($request->memberId));
        if ($member === null || $member->getId() === null) {
            throw new EntityNotFoundException('Member not found.');
        }

        $tagCocktailCounts = $this->recommendationRepository->getPositiveTagCocktailCounts($member->getId(), 6);
        $negativeTags = $this->recommendationRepository->getNegativeTagCocktailCounts($member->getId(), 6);
        $abvPreference = $this->recommendationRepository->getAbvPreference($member->getId());

        return new UserTasteProfileDTO(
            favoriteCocktailTags: array_map(static fn ($item) => ['name' => $item->name->toString(), 'count' => $item->count], $tagCocktailCounts),
            dislikedCocktailTags: array_map(static fn ($item) => ['name' => $item->name->toString(), 'count' => $item->count], $negativeTags),
            averageAbv: $abvPreference->averageAbv,
            abvDistribution: $abvPreference->distribution,
        );
    }
}
