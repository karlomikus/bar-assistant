<?php

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScrapeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create()
        );
    }

    public function test_cocktail_scrape()
    {
        $response = $this->postJson('/api/scrape/cocktail', [
            'url' => 'https://punchdrink.com/recipes/whiskey-peach-smash/'
        ]);

        $response->assertOk();
    }

    public function test_cocktail_scrape_fails_for_unknown_url()
    {
        $response = $this->postJson('/api/scrape/cocktail', [
            'url' => 'https://google.com'
        ]);

        $response->assertNotFound();
    }
}
