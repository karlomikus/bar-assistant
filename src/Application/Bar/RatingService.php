<?php

declare(strict_types=1);

namespace BarAssistant\Application\Bar;

use BarAssistant\Domain\Bar\Rating;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Bar\RatingRepository;
use BarAssistant\Application\Bar\DTO\RatingResult;
use BarAssistant\Application\Bar\DTO\RateCocktailRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class RatingService
{
    public function __construct(
        private RatingRepository $ratingRepository,
    ) {
    }

    public function rate(RateCocktailRequest $request): RatingResult
    {
        $memberId = new MemberId($request->memberId);
        $cocktailId = new CocktailId($request->cocktailId);

        $existingRating = $this->ratingRepository->findByCocktailAndMember($cocktailId, $memberId);

        if ($existingRating !== null) {
            $existingRating->updateValue($request->value);
            $savedRating = $this->ratingRepository->save($existingRating);
        } else {
            $rating = Rating::create(
                cocktailId: $cocktailId,
                memberId: $memberId,
                value: $request->value,
            );

            $savedRating = $this->ratingRepository->save($rating);
        }

        return $this->toResult($savedRating);
    }

    public function delete(int $memberId, int $cocktailId): void
    {
        $memberId = new MemberId($memberId);
        $cocktailId = new CocktailId($cocktailId);

        $rating = $this->ratingRepository->findByCocktailAndMember($cocktailId, $memberId);

        if ($rating === null) {
            throw new EntityNotFoundException('Rating not found.');
        }

        $ratingId = $rating->getId();
        if ($ratingId === null) {
            throw new EntityNotFoundException('Rating not found.');
        }

        $this->ratingRepository->delete($ratingId);
    }

    /**
     * @return RatingResult[]
     */
    public function getForCocktail(int $cocktailId): array
    {
        $cocktailId = new CocktailId($cocktailId);

        $ratings = $this->ratingRepository->findByCocktail($cocktailId);

        return array_map(
            fn (Rating $rating) => $this->toResult($rating),
            $ratings,
        );
    }

    /**
     * @return RatingResult[]
     */
    public function getForMember(int $memberId): array
    {
        $memberId = new MemberId($memberId);

        $ratings = $this->ratingRepository->findByMember($memberId);

        return array_map(
            fn (Rating $rating) => $this->toResult($rating),
            $ratings,
        );
    }

    /**
     * @return int[]
     */
    public function getLowRatedCocktailIds(int $memberId, int $maxValue = 2): array
    {
        $memberId = new MemberId($memberId);

        $cocktailIds = $this->ratingRepository->findLowRatedCocktailIds($memberId, $maxValue);

        return array_map(
            fn (CocktailId $id) => $id->value,
            $cocktailIds,
        );
    }

    private function toResult(Rating $rating): RatingResult
    {
        $ratingId = $rating->getId();

        return new RatingResult(
            id: $ratingId !== null ? $ratingId->value : 0,
            cocktailId: $rating->getCocktailId()->value,
            memberId: $rating->getMemberId()->value,
            value: $rating->getValue(),
            createdAt: $rating->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $rating->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
