<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_list_tags_response()
    {
        Tag::factory()->count(10)->create();

        $response = $this->getJson('/api/tags');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_show_tag_response()
    {
        $model = Tag::factory()->create([
            'name' => 'Test tag'
        ]);

        $response = $this->getJson('/api/tags/' . $model->id);

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test tag')
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_create_tag_response()
    {
        $response = $this->postJson('/api/tags/', [
            'name' => 'Test tag',
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->headers->get('Location'));
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'Test tag')
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_update_tag_response()
    {
        $model = Tag::factory()->create([
            'name' => 'Start tag',
        ]);

        $response = $this->putJson('/api/tags/' . $model->id, [
            'name' => 'Test tag',
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $model->id)
                ->where('data.name', 'Test tag')
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_delete_tag_response()
    {
        $tag = Tag::factory()->create([
            'name' => 'Start cat',
        ]);

        $response = $this->delete('/api/tags/' . $tag->id);

        $response->assertNoContent();

        $response->assertValidResponse();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
