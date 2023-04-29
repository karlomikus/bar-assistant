<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
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

        Spectator::using('open-api-spec.yml');

        $this->actingAs(User::factory()->create());
    }

    public function test_show_note_response()
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

        $response->assertValidResponse();
    }

    public function test_save_cocktail_note_response()
    {
        $cocktail = Cocktail::factory()->create();
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

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_save_cocktail_note_forbidden_response()
    {
        $cocktailUser = User::factory()->create(['is_admin' => false]);
        $cocktail = Cocktail::factory()->create([
            'user_id' => $cocktailUser->id,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => false]));

        $response = $this->postJson('/api/notes/', [
            'note' => 'A new note',
            'resource_id' => $cocktail->id,
            'resource' => 'cocktail',
        ]);

        $response->assertForbidden();
        $response->assertValidResponse();
    }

    public function test_delete_cocktail_note_response()
    {
        $cocktail = Cocktail::factory()->create();
        $note = $cocktail->addNote('Test note', auth()->user()->id);

        $response = $this->deleteJson('/api/notes/' . $note->id);

        $response->assertNoContent();
        $response->assertValidResponse();
    }
}
