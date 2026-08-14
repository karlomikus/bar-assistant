<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Export;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class Export implements Identity
{
    private ?ExportId $id = null;

    private function __construct(
        private readonly BarId $barId,
        private readonly UserId $createdUserId,
        private string $filename,
        private bool $isDone,
        private RecordTimestamps $recordTimestamps,
    ) {
    }

    public static function create(
        BarId $barId,
        UserId $createdUserId,
        string $filename,
    ): self {
        return new self(
            barId: $barId,
            createdUserId: $createdUserId,
            filename: $filename,
            isDone: false,
            recordTimestamps: RecordTimestamps::createdNow(),
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?ExportId
    {
        return $this->id;
    }

    public function setId(ExportId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing export');
        }

        $this->id = $id;

        return $this;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getCreatedUserId(): UserId
    {
        return $this->createdUserId;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function markAsDone(): self
    {
        $this->isDone = true;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        return $this;
    }
}
