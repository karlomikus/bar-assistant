<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Rating;

use DomainException;
use DateTimeImmutable;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\RatingValue;
use BarAssistant\Domain\User\UserId;

final class Rating implements Identity
{
    private ?RatingId $id = null;

    private function __construct(
        private readonly RateableId $rateableId,
        private readonly RateableType $type,
        private UserId $userId,
        private RatingValue $value,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        RateableId $rateableId,
        RateableType $type,
        UserId $userId,
        RatingValue $value,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            rateableId: $rateableId,
            type: $type,
            userId: $userId,
            value: $value,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?RatingId
    {
        return $this->id;
    }

    public function setId(RatingId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing rating');
        }

        $this->id = $id;

        return $this;
    }

    public function getRateableId(): RateableId
    {
        return $this->rateableId;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getValue(): RatingValue
    {
        return $this->value;
    }

    public function getType(): RateableType
    {
        return $this->type;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateValue(RatingValue $value): self
    {
        $this->value = $value;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }
}
