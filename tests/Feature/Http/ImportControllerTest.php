<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
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
}
