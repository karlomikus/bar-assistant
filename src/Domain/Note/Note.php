<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Note;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class Note implements Identity
{
    private ?NoteId $id = null;

    private function __construct(
        private readonly UserId $userId,
        private readonly int $noteableId,
        private readonly NoteableResourceType $noteableType,
        private string $noteContent,
        private RecordTimestamps $recordTimestamps,
    ) {
    }

    public static function create(
        UserId $userId,
        int $noteableId,
        NoteableResourceType $noteableType,
        string $noteContent,
        RecordTimestamps $recordTimestamps,
    ): self {
        return new self(
            userId: $userId,
            noteableId: $noteableId,
            noteableType: $noteableType,
            noteContent: $noteContent,
            recordTimestamps: $recordTimestamps,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?NoteId
    {
        return $this->id;
    }

    public function setId(NoteId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing note');
        }

        $this->id = $id;

        return $this;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getNoteableId(): int
    {
        return $this->noteableId;
    }

    public function getNoteableType(): NoteableResourceType
    {
        return $this->noteableType;
    }

    public function getNoteContent(): string
    {
        return $this->noteContent;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function updateNoteContent(string $newContent): void
    {
        $this->noteContent = $newContent;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();
    }
}
