<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Note\Note;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Note\NoteId;
use BarAssistant\Domain\User\UserId;
use Kami\Cocktail\Models\Note as ModelNote;
use BarAssistant\Domain\Note\NoteRepository;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Note\NoteableResourceType;

final class EloquentNoteRepository implements NoteRepository
{
    public function findById(NoteId $id): ?Note
    {
        $model = ModelNote::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Note $note): Note
    {
        $model = ModelNote::findOrNew($note->getId()?->value);
        $model->user_id = $note->getUserId()->value;
        $model->noteable_id = $note->getNoteableId();
        $model->noteable_type = match ($note->getNoteableType()) {
            NoteableResourceType::Cocktail => Cocktail::class,
        };
        $model->note = $note->getNoteContent();
        $model->created_at = $note->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');

        if ($note->getRecordTimestamps()->wasUpdated()) {
            $model->updated_at = $note->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }

        $model->save();

        if ($note->isTransient()) {
            $note->setId(new NoteId($model->id));
        }

        return $note;
    }

    public function delete(NoteId $id): void
    {
        ModelNote::destroy($id->value);
    }

    private static function map(ModelNote $model): Note
    {
        $note = Note::create(
            userId: new UserId($model->user_id),
            noteableId: $model->noteable_id,
            noteableType: match ($model->noteable_type) {
                Cocktail::class => NoteableResourceType::Cocktail,
                default => NoteableResourceType::Cocktail,
            },
            noteContent: $model->note,
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
        );

        $note->setId(new NoteId($model->id));

        return $note;
    }
}
