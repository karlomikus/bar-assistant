<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spectator\Spectator;

class ServerControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');
    }

    public function test_version_response()
    {
        $response = $this->getJson('/api/server/version');

        $response->assertStatus(200);

        $this->assertSame('Bar Assistant', $response['data']['name']);
        $this->assertNotNull($response['data']['version']);
        $this->assertNotNull($response['data']['search_host']);
        $this->assertNotNull($response['data']['search_version']);

        $response->assertValidResponse();
    }

    public function test_openapi_response()
    {
        $response = $this->getJson('/api/server/openapi');

        $response->assertStatus(200);

        $response->assertValidResponse();
    }
}
