<?php

declare(strict_types=1);

namespace BarAssistant\Application\Rating;

use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Rating\RateableType;
use BarAssistant\Domain\Rating\RatingRepository;
use BarAssistant\Application\Rating\DTO\RatingResult;
use BarAssistant\Application\Rating\DTO\RateCocktailRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class RatingService
{
    public function __construct(
        private RatingRepository $ratingRepository,
    ) {
    }

    public function rate(RateCocktailRequest $request): RatingResult
    {
        $memberId = new MemberId($request->barMembershipId);
        $cocktailId = new RateableId($request->cocktailId);

        $existingRating = $this->ratingRepository->findMemberRating($cocktailId, RateableType::Cocktail, $memberId);

        if ($existingRating !== null) {
            $existingRating->updateValue(RatingValue::create($request->value));
            $savedRating = $this->ratingRepository->save($existingRating);
        } else {
            $rating = Rating::create(
                rateableId: $cocktailId,
                type: RateableType::Cocktail,
                memberId: $memberId,
                value: RatingValue::create($request->value),
            );

            $savedRating = $this->ratingRepository->save($rating);
        }

        return $this->toResult($savedRating);
    }

    public function removeRating(int $barMembershipId, int $cocktailId): void
    {
        $memberId = new MemberId($barMembershipId);
        $cocktailId = new RateableId($cocktailId);

        $rating = $this->ratingRepository->findMemberRating($cocktailId, RateableType::Cocktail, $memberId);

        if ($rating === null || $rating->getId() === null) {
            throw new EntityNotFoundException('Rating not found.');
        }

        $this->ratingRepository->delete($rating->getId());
    }

    private function toResult(Rating $rating): RatingResult
    {
        $ratingId = $rating->getId();

        return new RatingResult(
            id: $ratingId !== null ? $ratingId->value : 0,
            cocktailId: $rating->getRateableId()->value,
            barMembershipId: $rating->getMemberId()->value,
            value: $rating->getValue()->value,
            createdAt: $rating->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $rating->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
