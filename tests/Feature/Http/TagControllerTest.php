<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Tag;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_tags_response(): void
    {
        Tag::factory()->for($this->barMembership->bar)->count(10)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $this->barMembership->bar_id);
        $response = $this->getJson('/api/tags');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_tag_response(): void
    {
        $model = Tag::factory()->for($this->barMembership->bar)->create([
            'name' => 'Test tag',
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
    }

    public function test_create_tag_response(): void
    {
        $this->withHeader('Bar-Assistant-Bar-Id', (string) $this->barMembership->bar_id);
        $response = $this->postJson('/api/tags', [
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
    }

    public function test_update_tag_response(): void
    {
        $model = Tag::factory()->for($this->barMembership->bar)->create([
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
    }

    public function test_delete_tag_response(): void
    {
        $tag = Tag::factory()->for($this->barMembership->bar)->create([
            'name' => 'Start cat',
        ]);

        $response = $this->delete('/api/tags/' . $tag->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
