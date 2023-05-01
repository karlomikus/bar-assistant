<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');

        $this->actingAs(User::factory()->create());
    }

    public function test_list_user_collections_response()
    {
        Collection::factory()->count(10)->create([
            'user_id' => auth()->user()->id,
        ]);

        $response = $this->getJson('/api/collections/');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_show_user_collection_response()
    {
        $collection = Collection::factory()->create([
            'name' => 'TEST',
            'description' => 'Description',
            'user_id' => auth()->user()->id,
        ]);

        $response = $this->getJson('/api/collections/' . $collection->id);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $collection->id)
                ->where('data.name', 'TEST')
                ->where('data.description', 'Description')
                ->where('data.cocktails', [])
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_create_collection_response()
    {
        $response = $this->postJson('/api/collections/', [
            'name' => 'TEST',
            'description' => 'Description',
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->headers->get('Location'));
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->has('data.id')
                ->where('data.name', 'TEST')
                ->where('data.description', 'Description')
                ->where('data.cocktails', [])
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_update_collections_response()
    {
        $model = Collection::factory()->create([
            'name' => 'TEST',
            'description' => 'Description',
            'user_id' => auth()->user()->id,
        ]);

        $response = $this->putJson('/api/collections/' . $model->id, [
            'name' => 'TEST 2',
            'description' => 'Description 2',
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $model->id)
                ->where('data.name', 'TEST 2')
                ->where('data.description', 'Description 2')
                ->where('data.cocktails', [])
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_delete_collection_response()
    {
        $model = Collection::factory()->create([
            'name' => 'TEST',
            'description' => 'Description',
            'user_id' => auth()->user()->id,
        ]);

        $response = $this->delete('/api/collections/' . $model->id);

        $response->assertNoContent();

        $response->assertValidResponse();

        $this->assertDatabaseMissing('collections', ['id' => $model->id]);
    }

    public function test_add_cocktail_to_collection()
    {
        $cocktail = Cocktail::factory()->create();
        $collection = Collection::factory()->create([
            'name' => 'TEST',
            'description' => 'Description',
            'user_id' => auth()->user()->id,
        ]);

        $response = $this->putJson('/api/collections/' . $collection->id . '/cocktails/' . $cocktail->id);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $collection->id)
                ->where('data.name', 'TEST')
                ->where('data.description', 'Description')
                ->where('data.cocktails', [$cocktail->id])
                ->etc()
        );

        $response->assertValidRequest();
        $response->assertValidResponse();
    }
}
