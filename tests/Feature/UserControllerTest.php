<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Spectator\Spectator;
use Kami\Cocktail\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Spectator::using('open-api-spec.yml');
    }

    public function test_current_user_response()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $user->id)
                ->where('data.email', $user->email)
                ->where('data.name', $user->name)
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_update_current_user_response()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/user', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $user->id)
                ->where('data.email', 'new@example.com')
                ->where('data.name', 'Test Guy')
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_update_current_user_with_password_response()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/user', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->has('data')
                ->where('data.id', $user->id)
                ->where('data.email', 'new@example.com')
                ->where('data.name', 'Test Guy')
                ->etc()
        );

        $response->assertValidResponse();
    }

    public function test_update_current_user_with_password_fail_response()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/user', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
            'password' => '12345',
            'password_confirmation' => '123451',
        ]);

        $response->assertUnprocessable();
        $response->assertValidResponse();
    }
}
