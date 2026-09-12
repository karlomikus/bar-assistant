<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Review;

use DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Common\ReviewContent;
use BarAssistant\Domain\Common\RecordTimestamps;

final class IngredientReview implements Identity
{
    private ?IngredientReviewId $id = null;

    private function __construct(
        private readonly int $ingredientId,
        private readonly MemberId $memberId,
        private ReviewContent $content,
        private ?Recommendation $recommendation,
        private RecordTimestamps $recordTimestamps,
    ) {
    }

    public static function create(
        int $ingredientId,
        MemberId $memberId,
        ReviewContent $content,
        ?Recommendation $recommendation,
        RecordTimestamps $recordTimestamps,
    ): self {
        return new self(
            ingredientId: $ingredientId,
            memberId: $memberId,
            content: $content,
            recommendation: $recommendation,
            recordTimestamps: $recordTimestamps,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?IngredientReviewId
    {
        return $this->id;
    }

    public function setId(IngredientReviewId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing ingredient review');
        }

        $this->id = $id;

        return $this;
    }

    public function getIngredientId(): int
    {
        return $this->ingredientId;
    }

    public function getMemberId(): MemberId
    {
        return $this->memberId;
    }

    public function getContent(): ReviewContent
    {
        return $this->content;
    }

    public function getRecommendation(): ?Recommendation
    {
        return $this->recommendation;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function update(ReviewContent $content, ?Recommendation $recommendation): void
    {
        $this->content = $content;
        $this->recommendation = $recommendation;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();
    }
}
