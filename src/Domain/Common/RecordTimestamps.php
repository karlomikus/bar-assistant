<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use DateTimeImmutable;

final readonly class RecordTimestamps
{
    private function __construct(
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public static function createdNow(): self
    {
        return new self(new DateTimeImmutable(), null);
    }

    public static function createdAt(DateTimeImmutable $createdAt): self
    {
        return new self($createdAt, null);
    }

    public function updatedNow(): self
    {
        return new self($this->createdAt, new DateTimeImmutable());
    }

    public function updatedAt(?DateTimeImmutable $updatedAt): self
    {
        return new self($this->createdAt, $updatedAt);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function wasUpdated(): bool
    {
        return $this->updatedAt !== null;
    }
}
