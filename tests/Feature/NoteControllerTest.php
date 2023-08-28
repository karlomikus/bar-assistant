<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_list_notes_response(): void
    {
        $cocktail = Cocktail::factory()->create();
        $cocktail->addNote('Test note 1', auth()->user()->id);
        $cocktail->addNote('Test note 2', auth()->user()->id);
        $cocktail->addNote('Test note 3', auth()->user()->id);

        $response = $this->getJson('/api/notes');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_list_notes_by_cocktail_response(): void
    {
        $cocktail1 = Cocktail::factory()->create();
        $cocktail2 = Cocktail::factory()->create();
        $cocktail1->addNote('Test note 1', auth()->user()->id);
        $cocktail2->addNote('Test note 2', auth()->user()->id);

        $response = $this->getJson('/api/notes?filter[cocktail_id]=' . $cocktail1->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_note_response(): void
    {
        $cocktail = Cocktail::factory()->create();
        $note = $cocktail->addNote('Test note', auth()->user()->id);

        $response = $this->getJson('/api/notes/' . $note->id);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.note', 'Test note')
                ->where('data.user_id', auth()->user()->id)
                ->has('data.created_at')
                ->etc()
        );
    }

    public function test_save_cocktail_note_response(): void
    {
        $this->setupBar();
        $cocktail = Cocktail::factory()->create(['bar_id' => 1]);
        $response = $this->postJson('/api/notes/', [
            'note' => 'A new note',
            'resource_id' => $cocktail->id,
            'resource' => 'cocktail',
        ]);

        $response->assertCreated();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.id')
                ->where('data.note', 'A new note')
                ->where('data.user_id', auth()->user()->id)
                ->has('data.created_at')
                ->etc()
        );
    }

    public function test_save_cocktail_note_forbidden_response(): void
    {
        $cocktailUser = User::factory()->create();
        $cocktail = Cocktail::factory()->create([
            'created_user_id' => $cocktailUser->id,
        ]);

        $this->actingAs(User::factory()->create());

        $response = $this->postJson('/api/notes/', [
            'note' => 'A new note',
            'resource_id' => $cocktail->id,
            'resource' => 'cocktail',
        ]);

        $response->assertForbidden();
    }

    public function test_delete_cocktail_note_response(): void
    {
        $cocktail = Cocktail::factory()->create();
        $note = $cocktail->addNote('Test note', auth()->user()->id);

        $response = $this->deleteJson('/api/notes/' . $note->id);

        $response->assertNoContent();
    }
}
