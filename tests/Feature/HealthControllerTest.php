<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    public function test_version_response()
    {
        $response = $this->getJson('/api/server/version');

        $response->assertStatus(200);

        $this->assertSame('Bar Assistant', $response['data']['name']);
        $this->assertNotNull($response['data']['version']);
    }
}
