<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Concerns;

use Kami\Cocktail\Models\Note;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasNotes
{
    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function addNote(string $noteBody, int $userId): Note
    {
        $note = new Note();

        $note->note = $noteBody;
        $note->user_id = $userId;

        $this->notes()->save($note);

        return $note;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getUserNotes(int $userId): Collection
    {
        return $this->notes()->where('user_id', $userId)->get();
    }

    public function deleteUserNote(int $userId): void
    {
        $this->notes()->where('user_id', $userId)->delete();
    }

    public function deleteNotes(): void
    {
        $this->notes()->delete();
    }
}
