<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Support;

use DateTimeImmutable;
use BarAssistant\Domain\User\UserId;

final readonly class Authors
{
    private function __construct(
        private UserId $createdBy,
        private ?DateTimeImmutable $createdAt = null,
        private ?UserId $updatedBy = null,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function getCreatedBy(): UserId
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedBy(): ?UserId
    {
        return $this->updatedBy;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function wasUpdated(): bool
    {
        return $this->updatedBy !== null;
    }

    public function markAsUpdated(UserId $userId, DateTimeImmutable $updatedAt): self
    {
        return new self(
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            updatedBy: $userId,
            updatedAt: $updatedAt,
        );
    }

    public static function create(UserId $userId, ?DateTimeImmutable $createdAt = null): self
    {
        return new self(
            createdBy: $userId,
            createdAt: $createdAt ?? new DateTimeImmutable(),
        );
    }
}
