<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Note;

interface NoteRepository
{
    public function findById(NoteId $id): ?Note;

    public function save(Note $note): Note;

    public function delete(NoteId $id): void;
}
