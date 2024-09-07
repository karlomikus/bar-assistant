<?php

namespace Tests\Feature\Http;

use Tests\TestCase;

class ServerControllerTest extends TestCase
{
    public function test_version_response(): void
    {
        $response = $this->getJson('/api/server/version');

        $response->assertStatus(200);

        $this->assertNotNull($response['data']['type']);
        $this->assertNotNull($response['data']['version']);
        $this->assertNotNull($response['data']['search_host']);
        $this->assertNotNull($response['data']['search_version']);
    }

    public function test_openapi_response(): void
    {
        $response = $this->getJson('/api/server/openapi');

        $response->assertStatus(200);
    }
}
