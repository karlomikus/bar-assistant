<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Models\User;
use Spectator\Spectator;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');
    }

    public function test_authenticate_response()
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'my-test-password'
        ]);

        $response->assertStatus(200);
        $response->assertValidRequest();
        $response->assertValidResponse();
    }

    public function test_logout_response()
    {
        $this->actingAs(
            User::factory()->create()
        );

        // Logout and check headers
        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertValidResponse();
    }

    public function test_register_response()
    {
        // Logout and check headers
        $response = $this->postJson('/api/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        $response->assertSuccessful();
        $response->assertValidRequest();
        $response->assertValidResponse();
    }
}
