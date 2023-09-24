<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
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

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_list_tags_response(): void
    {
        $bar = $this->setupBar();
        Tag::factory()->count(10)->create(['bar_id' => $bar->id]);

        $response = $this->getJson('/api/tags?bar_id=' . $bar->id);

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
        $bar = $this->setupBar();
        $model = Tag::factory()->create([
            'name' => 'Test tag',
            'bar_id' => $bar->id
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
        $this->setupBar();
        $response = $this->postJson('/api/tags?bar_id=1', [
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
        $bar = $this->setupBar();
        $model = Tag::factory()->create([
            'name' => 'Start tag',
            'bar_id' => $bar->id
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
        $bar = $this->setupBar();
        $tag = Tag::factory()->create([
            'name' => 'Start cat',
            'bar_id' => $bar->id,
        ]);

        $response = $this->delete('/api/tags/' . $tag->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
