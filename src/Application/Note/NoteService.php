<?php

declare(strict_types=1);

namespace BarAssistant\Application\Note;

use BarAssistant\Domain\Note\Note;
use BarAssistant\Domain\Note\NoteId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Note\NoteRepository;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Application\Note\DTO\NoteResult;
use BarAssistant\Application\Note\DTO\CreateNoteRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class NoteService
{
    private const RESOURCE_TYPE_MAP = [
        'cocktail' => 'Kami\Cocktail\Models\Cocktail',
    ];

    public function __construct(
        private NoteRepository $noteRepository,
    ) {
    }

    public function createNote(CreateNoteRequest $request): NoteResult
    {
        $resourceModelClass = $this->resolveResourceModelClass($request->resource);

        $note = Note::create(
            userId: new UserId($request->userId),
            noteableId: (string) $request->resourceId,
            noteableType: $resourceModelClass,
            noteContent: $request->note,
            recordTimestamps: RecordTimestamps::createdNow(),
        );

        $note = $this->noteRepository->save($note);

        return NoteResult::fromNote($note);
    }

    public function getNote(int $noteId): NoteResult
    {
        $note = $this->noteRepository->findById(new NoteId($noteId));

        if ($note === null) {
            throw new EntityNotFoundException('Note not found');
        }

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

    private function resolveResourceModelClass(string $resource): string
    {
        if (!isset(self::RESOURCE_TYPE_MAP[$resource])) {
            throw new \InvalidArgumentException("Unsupported resource type: {$resource}");
        }

        return self::RESOURCE_TYPE_MAP[$resource];
    }
}
