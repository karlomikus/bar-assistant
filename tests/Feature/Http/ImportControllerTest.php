<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_scrapes_from_url(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);
        $response = $this->postJson('/api/import/scrape', [
            'source' => 'https://barassistant.app'
        ]);

        $response->assertSuccessful();
    }

    public function test_import_cocktail(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $source = file_get_contents(base_path('tests/fixtures/external/recipe.json'));

        $response = $this->postJson('/api/import/cocktail', [
            'source' => $source,
            'duplicate_actions' => 'none',
        ]);

        $response->assertSuccessful();
    }

    public function test_import_cocktail_array_created_and_skip_overwrite(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $base = json_decode((string) file_get_contents(base_path('tests/fixtures/external/recipe.json')), true);
        $first = $base;
        $first['recipe']['name'] = 'Bulk Test 1';
        $second = $base;
        $second['recipe']['name'] = 'Bulk Test 2';

        // First: create two
        $resp = $this->postJson('/api/import/cocktail', [
            'source' => [$first, $second],
            'duplicate_actions' => 'none',
        ]);
        $resp->assertSuccessful();
        $resp->assertJsonPath('data.counts.created', 2);

        // Second: try again with skip
        $resp = $this->postJson('/api/import/cocktail', [
            'source' => [$first, $second],
            'duplicate_actions' => 'skip',
        ]);
        $resp->assertSuccessful();
        $resp->assertJsonPath('data.counts.skipped', 2);

        // Third: overwrite
        $resp = $this->postJson('/api/import/cocktail', [
            'source' => [$first, $second],
            'duplicate_actions' => 'overwrite',
        ]);
        $resp->assertSuccessful();
        $resp->assertJsonPath('data.counts.overwritten', 2);
    }

    public function test_import_cocktail_array_partial_failure_continues(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $valid = json_decode((string) file_get_contents(base_path('tests/fixtures/external/recipe.json')), true);
        $valid['recipe']['name'] = 'Bulk Partial OK';
        $invalid = ['recipe' => ['name' => 'Broken'], 'ingredients' => 'not-an-array'];

        $resp = $this->postJson('/api/import/cocktail', [
            'source' => [$valid, $invalid],
            'duplicate_actions' => 'none',
        ]);

        $resp->assertSuccessful();
        $resp->assertJsonPath('data.counts.total', 2);
        $resp->assertJsonPath('data.counts.created', 1);
        $resp->assertJsonPath('data.counts.failed', 1);
    }

    public function test_import_cocktail_array_too_many_items_returns_413(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $base = json_decode((string) file_get_contents(base_path('tests/fixtures/external/recipe.json')), true);
        $payload = [];
        for ($i = 0; $i < 501; $i++) {
            $copy = $base;
            $copy['recipe']['name'] = 'Over Limit ' . $i;
            $payload[] = $copy;
        }

        $resp = $this->postJson('/api/import/cocktail', [
            'source' => $payload,
            'duplicate_actions' => 'none',
        ]);

        $resp->assertStatus(413);
    }

    public function test_import_csv_ingredients_from_file(): void
    {
        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        $source = file_get_contents(base_path('tests/fixtures/ingredients.csv'));

        $response = $this->postJson('/api/import/ingredients', [
            'source' => UploadedFile::fake()->createWithContent('import.csv', $source),
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseCount('ingredients', 4);
        $this->assertDatabaseHas('ingredients', [
            'name' => 'Campari',
            'slug' => 'campari-' . $membership->bar_id,
            'strength' => 40,
            'description' => 'Bitter liquer',
            'origin' => 'Italy',
            'color' => '#008800',
            'bar_id' => $membership->bar_id,
            'created_user_id' => $membership->user_id,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'name' => 'gin',
            'slug' => 'gin-' . $membership->bar_id,
            'strength' => 23.3,
            'description' => null,
            'origin' => null,
            'color' => null,
            'bar_id' => $membership->bar_id,
            'created_user_id' => $membership->user_id,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'name' => 'Whiskey',
            'slug' => 'whiskey-' . $membership->bar_id,
            'strength' => 0,
            'description' => null,
            'origin' => null,
            'color' => null,
            'bar_id' => $membership->bar_id,
            'created_user_id' => $membership->user_id,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'name' => 'empty',
            'slug' => 'empty-' . $membership->bar_id,
            'strength' => 0,
            'description' => null,
            'origin' => null,
            'color' => null,
            'bar_id' => $membership->bar_id,
            'created_user_id' => $membership->user_id,
        ]);

        $response = $this->postJson('/api/import/ingredients', [
            'source' => UploadedFile::fake()->createWithContent('test.jpg', $source),
        ]);

        $response->assertUnprocessable();
    }

    public function test_import_scrapes_from_html_content(): void
    {
        Http::fake();

        $membership = $this->setupBarMembership();
        $this->actingAs($membership->user);

        $this->withHeader('Bar-Assistant-Bar-Id', (string) $membership->bar_id);

        // The fixture is actually different recipe to test that no external request is used
        $response = $this->postJson('/api/import/scrape', [
            'source' => 'https://punchdrink.com/recipes/prado/',
            'html_content' => file_get_contents(base_path('tests/fixtures/scraper-html-content.html')),
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.schema.recipe.name', 'Negroni');
    }
}
