<?php

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_logout_response()
    {
        $this->actingAs(
            User::factory()->create()
        );

        // Logout and check headers
        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
    }

    public function test_register_response()
    {
        // Logout and check headers
        $response = $this->postJson('/api/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        $response->assertCreated();
    }
}
