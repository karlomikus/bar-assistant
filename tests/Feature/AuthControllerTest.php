<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticate_response(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'my-test-password'
        ]);

        $response->assertOk();
        $this->assertNotNull($response['data']['token']);
    }

    public function test_authenticate_not_found_response(): void
    {
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@test2.com',
            'password' => 'my-test-password'
        ]);

        $response->assertNotFound();
    }

    public function test_logout_response(): void
    {
        $this->actingAs(
            User::factory()->create()
        );

        // Logout and check headers
        $response = $this->postJson('/api/logout');

        $response->assertNoContent();
    }

    public function test_register_response(): void
    {
        // Logout and check headers
        $response = $this->postJson('/api/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.name', 'Test Guy');
        $response->assertJsonPath('data.email', 'test@test.com');
    }
}
