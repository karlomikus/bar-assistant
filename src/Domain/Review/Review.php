<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Review;

use DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Common\ReviewContent;
use BarAssistant\Domain\Common\RecordTimestamps;

final class Review implements Identity
{
    private ?ReviewId $id = null;

    private function __construct(
        private readonly int $cocktailId,
        private readonly MemberId $memberId,
        private ReviewContent $content,
        private RecordTimestamps $recordTimestamps,
    ) {
    }

    public static function create(
        int $cocktailId,
        MemberId $memberId,
        ReviewContent $content,
        RecordTimestamps $recordTimestamps,
    ): self {
        return new self(
            cocktailId: $cocktailId,
            memberId: $memberId,
            content: $content,
            recordTimestamps: $recordTimestamps,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?ReviewId
    {
        return $this->id;
    }

    public function setId(ReviewId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing review');
        }

        $this->id = $id;

        return $this;
    }

    public function getCocktailId(): int
    {
        return $this->cocktailId;
    }

    public function getMemberId(): MemberId
    {
        return $this->memberId;
    }

    public function getContent(): ReviewContent
    {
        return $this->content;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function updateContent(ReviewContent $content): void
    {
        $this->content = $content;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();
    }
}
