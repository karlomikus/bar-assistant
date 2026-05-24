<?php

declare(strict_types=1);

namespace BarAssistant\Application\Note;

use BarAssistant\Domain\Note\Note;
use BarAssistant\Domain\Note\NoteId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Note\NoteRepository;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Application\Note\DTO\NoteResult;
use BarAssistant\Domain\Note\NoteableResourceType;
use BarAssistant\Application\Note\DTO\CreateNoteRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Exception\ApplicationServiceException;

final readonly class NoteService
{
    public function __construct(
        private NoteRepository $noteRepository,
    ) {
    }

    public function createNote(CreateNoteRequest $request): NoteResult
    {
        $note = Note::create(
            userId: new UserId($request->userId),
            noteableId: $request->resourceId,
            noteableType: match ($request->resource) {
                'cocktail' => NoteableResourceType::Cocktail,
                default => throw new ApplicationServiceException('Unknown resource type'),
            },
            noteContent: $request->note,
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $note = $this->noteRepository->save($note);

        return NoteResult::fromNote($note);
    }

    public function deleteNote(int $noteId): void
    {
        $note = $this->noteRepository->findById(new NoteId($noteId));

        if ($note === null) {
            throw new EntityNotFoundException('Note not found');
        }

        $this->noteRepository->delete(new NoteId($noteId));
    }
}
