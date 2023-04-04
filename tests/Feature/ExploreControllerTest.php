<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\Cocktail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExploreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_cocktail_with_public_link()
    {
        Cocktail::factory()->create([
            'public_id' => 'TEST123456789'
        ]);

        $response = $this->getJson('/api/explore/cocktails/TEST123456789');

        $response->assertOk();
    }

    public function test_dont_show_cocktail_without_public_link()
    {
        Cocktail::factory()->create();

        $response = $this->getJson('/api/explore/cocktails/TEST123456789');

        $response->assertNotFound();
    }

    public function test_dont_show_cocktail_with_wrong_public_link()
    {
        Cocktail::factory()->create([
            'public_id' => 'TEST123456789'
        ]);

        $response = $this->getJson('/api/explore/cocktails/DEMO123456789');

        $response->assertNotFound();
    }
}
