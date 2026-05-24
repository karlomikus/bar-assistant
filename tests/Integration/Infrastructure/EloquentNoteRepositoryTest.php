<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use DateTimeImmutable;
use Kami\Cocktail\Models\User;
use BarAssistant\Domain\Note\Note;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Note\NoteId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Note\NoteableResourceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentNoteRepository;

final class EloquentNoteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function createPersistedNote(): Note
    {
        $user = User::factory()->create();

        $note = Note::create(
            userId: new UserId($user->id),
            noteableId: 1,
            noteableType: NoteableResourceType::Cocktail,
            noteContent: 'Test note',
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 12:00:00')),
        );

        return (new EloquentNoteRepository())->save($note);
    }

    public function test_it_saves_note(): void
    {
        $repository = new EloquentNoteRepository();
        $user = User::factory()->create();

        $note = Note::create(
            userId: new UserId($user->id),
            noteableId: 1,
            noteableType: NoteableResourceType::Cocktail,
            noteContent: 'Test note',
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 12:00:00')),
        );

        $note = $repository->save($note);

        $this->assertDatabaseHas('notes', [
            'id' => $note->getId()->value,
            'user_id' => $user->id,
            'noteable_id' => 1,
            'noteable_type' => Cocktail::class,
            'note' => 'Test note',
            'created_at' => '2025-01-01 12:00:00',
        ]);
    }

    public function test_it_updates_note(): void
    {
        $repository = new EloquentNoteRepository();
        $note = $this->createPersistedNote();

        $note->updateNoteContent('Updated content');

        $repository->save($note);

        $this->assertDatabaseHas('notes', [
            'id' => $note->getId()->value,
            'note' => 'Updated content',
        ]);
    }

    public function test_it_finds_note_by_id(): void
    {
        $repository = new EloquentNoteRepository();
        $persistedNote = $this->createPersistedNote();

        $foundNote = $repository->findById($persistedNote->getId());

        $this->assertNotNull($foundNote);
        $this->assertSame('Test note', $foundNote->getNoteContent());
    }

    public function test_it_returns_null_for_non_existent_note(): void
    {
        $repository = new EloquentNoteRepository();
        $foundNote = $repository->findById(new NoteId(9999));

        $this->assertNull($foundNote);
    }

    public function test_it_deletes_note(): void
    {
        $repository = new EloquentNoteRepository();
        $note = $this->createPersistedNote();

        $repository->delete($note->getId());

        $this->assertDatabaseMissing('notes', ['id' => $note->getId()->value]);
    }
}
