<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Support;

use DateTimeImmutable;

final readonly class RecordTimestamps
{
    private function __construct(
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
