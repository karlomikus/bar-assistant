<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
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
        $response = $this->postJson('/api/import/cocktail', [
            'source' => 'https://punchdrink.com/recipes/whiskey-peach-smash/'
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Whiskey Peach Smash');
        $response->assertJsonCount(1, 'data.images');
        $response->assertJsonCount(5, 'data.ingredients');
    }

    public function test_cocktail_scrape_fails_for_unknown_url(): void
    {
        $response = $this->postJson('/api/import/cocktail', [
            'source' => 'https://google.com'
        ]);

        $response->assertBadRequest();
    }

    public function test_cocktail_scrape_from_json(): void
    {
        $source = file_get_contents(base_path('tests/fixtures/import.json'));
        $response = $this->postJson('/api/import/cocktail?type=json', [
            'source' => $source
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Old Fashioned');
        $response->assertJsonCount(4, 'data.ingredients');
        $response->assertJsonCount(1, 'data.images');
    }

    public function test_cocktail_scrape_from_json_fails_bad_format(): void
    {
        $response = $this->postJson('/api/import/cocktail?type=json', [
            'source' => 'TEST'
        ]);

        $response->assertBadRequest();
    }

    public function test_cocktail_scrape_from_yaml(): void
    {
        $source = file_get_contents(base_path('tests/fixtures/import.yaml'));
        $response = $this->postJson('/api/import/cocktail?type=yaml', [
            'source' => $source
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Old Fashioned');
        $response->assertJsonCount(4, 'data.ingredients');
        $response->assertJsonCount(1, 'data.images');
    }

    public function test_cocktail_scrape_from_yaml_fails_bad_format(): void
    {
        $response = $this->postJson('/api/import/cocktail?type=yaml', [
            'source' => "{-- Test \n}\n-test"
        ]);

        $response->assertBadRequest();
    }
}
