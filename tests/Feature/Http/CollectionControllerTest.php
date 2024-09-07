<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Kami\Cocktail\Models\Collection;
use Kami\Cocktail\Models\BarMembership;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    private BarMembership $barMembership;

    public function setUp(): void
    {
        parent::setUp();

        $this->barMembership = $this->setupBarMembership();
        $this->actingAs($this->barMembership->user);
    }

    public function test_list_user_collections_response(): void
    {
        Collection::factory()->recycle($this->barMembership->bar)->for($this->barMembership)->count(10)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $this->barMembership->bar_id);
        $response = $this->getJson('/api/collections');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 10)
                ->etc()
        );
    }

    public function test_show_user_collection_response(): void
    {
        $collection = Collection::factory()->for($this->barMembership)->create([
            'name' => 'TEST',
            'description' => 'Description',
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
    }

    public function test_create_collection_response(): void
    {
        $cocktail = Cocktail::factory()->for($this->barMembership->bar)->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $this->barMembership->bar_id);
        $response = $this->postJson('/api/collections', [
            'name' => 'TEST',
            'description' => 'Description',
            'cocktails' => [$cocktail->id]
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
                ->where('data.cocktails.0.id', $cocktail->id)
                ->etc()
        );
    }

    public function test_create_collection_does_not_add_cocktail_from_another_bar_response(): void
    {
        $cocktail1 = Cocktail::factory()->for($this->barMembership->bar)->create();
        $cocktail2 = Cocktail::factory()->create();

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $this->barMembership->bar_id);
        $response = $this->postJson('/api/collections', [
            'name' => 'TEST',
            'description' => 'Description',
            'cocktails' => [$cocktail1->id, $cocktail2->id]
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
                ->has('data.cocktails', 1)
                ->where('data.cocktails.0.id', $cocktail1->id)
                ->etc()
        );
    }

    public function test_update_collections_response(): void
    {
        $model = Collection::factory()->for($this->barMembership)->create([
            'name' => 'TEST',
            'description' => 'Description',
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
    }

    public function test_delete_collection_response(): void
    {
        $model = Collection::factory()->for($this->barMembership)->create([
            'name' => 'TEST',
            'description' => 'Description',
        ]);

        $response = $this->delete('/api/collections/' . $model->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('collections', ['id' => $model->id]);
    }

    public function test_list_shared_collections_in_a_bar(): void
    {
        Collection::factory()->for($this->barMembership)->count(5)->create();
        Collection::factory()->for($this->barMembership)->count(3)->create([
            'is_bar_shared' => true
        ]);

        $response = $this->getJson('/api/bars/' . $this->barMembership->bar_id . '/collections');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data', 3)
                ->etc()
        );
    }

    public function test_sync_cocktails_in_collection(): void
    {
        $cocktailsInCollection = Cocktail::factory()->for($this->barMembership->bar)->count(3);

        $model = Collection::factory()
            ->for($this->barMembership)
            ->has($cocktailsInCollection)
            ->create([
                'name' => 'TEST',
                'description' => 'Description',
            ]);

        $newCocktailToAdd = Cocktail::factory()->for($this->barMembership->bar)->create();

        $response = $this->putJson('/api/collections/' . $model->id . '/cocktails', [
            'cocktails' => [$newCocktailToAdd->id],
        ]);

        $response->assertSuccessful();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data.cocktails', 1)
                ->etc()
        );
    }
}
