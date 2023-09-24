<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\CocktailMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_cocktail_scrape_from_valid_url(): void
    {
        $this->setupBar();
        $response = $this->postJson('/api/import/cocktail?bar_id=1', [
            'source' => 'https://punchdrink.com/recipes/whiskey-peach-smash/'
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Whiskey Peach Smash');
        $response->assertJsonCount(1, 'data.images');
        $response->assertJsonCount(5, 'data.ingredients');
    }

    public function test_cocktail_scrape_fails_safely_for_unknown_url(): void
    {
        $this->setupBar();
        $response = $this->postJson('/api/import/cocktail?bar_id=1', [
            'source' => 'https://google.com'
        ]);

        $response->assertOk();
    }

    public function test_cocktail_scrape_from_json(): void
    {
        $this->setupBar();
        $source = file_get_contents(base_path('tests/fixtures/import.json'));
        $response = $this->postJson('/api/import/cocktail?bar_id=1&type=json', [
            'source' => $source
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Old Fashioned');
        $response->assertJsonPath('data.instructions', 'Fixture instruction');
        $response->assertJsonPath('data.garnish', 'Garnish from fixture.');
        $response->assertJsonPath('data.description', 'Fixture description');
        $response->assertJsonPath('data.source', 'fixture-source');
        $response->assertJsonPath('data.glass', 'Lowball');
        $response->assertJsonPath('data.method', 'Build');
        $response->assertJsonCount(2, 'data.ingredients');
        $response->assertJsonPath('data.ingredients.0.sort', 1);
        $response->assertJsonPath('data.ingredients.0.name', 'Bourbon Whiskey');
        $response->assertJsonPath('data.ingredients.0.amount', 45);
        $response->assertJsonPath('data.ingredients.0.optional', false);
        $response->assertJsonPath('data.ingredients.0.units', 'ml');
        $response->assertJsonPath('data.ingredients.0.category', 'Spirits');
        $response->assertJsonPath('data.ingredients.0.description', 'Barrel-aged distilled liquor made primarily from corn.');
        $response->assertJsonPath('data.ingredients.0.strength', 40);
        $response->assertJsonPath('data.ingredients.0.origin', 'North America');
        $response->assertJsonCount(0, 'data.ingredients.0.substitutes');
        $response->assertJsonPath('data.ingredients.1.sort', 2);
        $response->assertJsonPath('data.ingredients.1.name', 'Water');
        $response->assertJsonPath('data.ingredients.1.amount', 2);
        $response->assertJsonPath('data.ingredients.1.optional', true);
        $response->assertJsonPath('data.ingredients.1.units', 'dashes');
        $response->assertJsonPath('data.ingredients.1.category', 'Beverages');
        $response->assertJsonPath('data.ingredients.1.description', 'It\'s water.');
        $response->assertJsonPath('data.ingredients.1.strength', 0);
        $response->assertJsonPath('data.ingredients.1.origin', 'Worldwide');
        $response->assertJsonPath('data.ingredients.1.substitutes', ['Club soda', 'H20']);
        $response->assertJsonCount(2, 'data.ingredients.1.substitutes');
        $response->assertJsonCount(1, 'data.images');
        $response->assertJsonPath('data.images.0.url', null);
        $response->assertJsonPath('data.images.0.copyright', 'Localhost');
    }

    public function test_cocktail_scrape_from_json_save(): void
    {
        $bar = $this->setupBar();
        CocktailMethod::factory()->create(['bar_id' => $bar->id, 'name' => 'Build']);
        $source = file_get_contents(base_path('tests/fixtures/import.json'));
        $response = $this->postJson('/api/import/cocktail?bar_id=1&type=json&save=1', [
            'source' => $source
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Old Fashioned');
        $response->assertJsonPath('data.instructions', 'Fixture instruction');
        $response->assertJsonPath('data.garnish', 'Garnish from fixture.');
        $response->assertJsonPath('data.description', 'Fixture description');
        $response->assertJsonPath('data.source', 'fixture-source');
        $response->assertJsonPath('data.glass.name', 'Lowball');
        $response->assertJsonPath('data.method.name', 'Build');
        $response->assertJsonCount(2, 'data.ingredients');
        $response->assertJsonCount(3, 'data.tags');
    }

    public function test_cocktail_scrape_from_json_fails_bad_format(): void
    {
        $this->setupBar();
        $response = $this->postJson('/api/import/cocktail?bar_id=1&type=json', [
            'source' => 'TEST'
        ]);

        $response->assertBadRequest();
    }

    public function test_cocktail_scrape_from_yaml(): void
    {
        $this->setupBar();
        $source = file_get_contents(base_path('tests/fixtures/import.yaml'));
        $response = $this->postJson('/api/import/cocktail?bar_id=1&type=yaml', [
            'source' => $source
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Old Fashioned');
        $response->assertJsonCount(4, 'data.ingredients');
        $response->assertJsonCount(1, 'data.images');
    }

    public function test_cocktail_scrape_from_yaml_fails_bad_format(): void
    {
        $this->setupBar();
        $response = $this->postJson('/api/import/cocktail?type=yaml&bar_id=1', [
            'source' => "{-- Test \n}\n-test"
        ]);

        $response->assertBadRequest();
    }
}
