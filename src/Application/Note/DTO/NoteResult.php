<?php

declare(strict_types=1);

namespace BarAssistant\Application\Note\DTO;

use BarAssistant\Domain\Note\Note;

final readonly class NoteResult
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $noteableId,
        public string $noteableType,
        public string $note,
        public string $createdAt,
    ) {
    }

    public static function fromNote(Note $note): self
    {
        return new self(
            id: $note->getId()->value ?? 0,
            userId: $note->getUserId()->value,
            noteableId: $note->getNoteableId(),
            noteableType: $note->getNoteableType()->name,
            note: $note->getNoteContent(),
            createdAt: $note->getRecordTimestamps()->getCreatedAt()->format('c'),
        );
    }
}
