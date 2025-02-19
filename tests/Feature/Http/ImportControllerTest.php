<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
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
        $this->assertDatabaseCount('ingredient_categories', 2);
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
}
