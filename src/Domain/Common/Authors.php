<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use BarAssistant\Domain\User\UserId;

final readonly class Authors
{
    private function __construct(
        private UserId $createdBy,
        private ?UserId $updatedBy = null,
    ) {
    }

    public function getCreatedBy(): UserId
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?UserId
    {
        return $this->updatedBy;
    }

    public static function createdBy(UserId $userId): self
    {
        return new self($userId, null);
    }

    public function updatedBy(?UserId $userId): self
    {
        return new self($this->createdBy, $userId);
    }

    public function isUpdated(): bool
    {
        return $this->updatedBy !== null;
    }
}
