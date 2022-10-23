<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    public function test_version_response()
    {
        $response = $this->get('/api/version');

        $response->assertStatus(200);
    }
}
