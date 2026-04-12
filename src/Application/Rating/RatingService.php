<?php

declare(strict_types=1);

namespace BarAssistant\Application\Rating;

use BarAssistant\Domain\Rating\Rating;
use BarAssistant\Domain\Rating\RatingRepository;
use BarAssistant\Application\Rating\DTO\RatingResult;
use BarAssistant\Application\Rating\DTO\RateCocktailRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\Rating\RateableId;
use BarAssistant\Domain\Rating\RateableType;
use BarAssistant\Domain\User\UserId;

final readonly class RatingService
{
    public function __construct(
        private RatingRepository $ratingRepository,
    ) {
    }

    public function rate(RateCocktailRequest $request): RatingResult
    {
        $userId = new UserId($request->userId);
        $cocktailId = new RateableId($request->cocktailId);

        $existingRating = $this->ratingRepository->findUserRating($cocktailId, RateableType::Cocktail, $userId);

        if ($existingRating !== null) {
            $existingRating->updateValue(RatingValue::create($request->value));
            $savedRating = $this->ratingRepository->save($existingRating);
        } else {
            $rating = Rating::create(
                rateableId: $cocktailId,
                type: RateableType::Cocktail,
                userId: $userId,
                value: RatingValue::create($request->value),
            );

            $savedRating = $this->ratingRepository->save($rating);
        }

        return $this->toResult($savedRating);
    }

    public function removeRating(int $userId, int $cocktailId): void
    {
        $userId = new UserId($userId);
        $cocktailId = new RateableId($cocktailId);

        $rating = $this->ratingRepository->findUserRating($cocktailId, RateableType::Cocktail, $userId);

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
            userId: $rating->getUserId()->value,
            value: $rating->getValue()->value,
            createdAt: $rating->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $rating->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
