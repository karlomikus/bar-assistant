<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServerControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_version_response()
    {
        $response = $this->getJson('/api/server/version');

        $response->assertStatus(200);

        $this->assertNotNull($response['data']['version']);
        $this->assertNotNull($response['data']['search_host']);
        $this->assertNotNull($response['data']['search_version']);
    }

    public function test_openapi_response()
    {
        $response = $this->getJson('/api/server/openapi');

        $response->assertStatus(200);
    }
}
