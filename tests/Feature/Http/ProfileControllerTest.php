<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_current_user_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/profile');

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
    }

    public function test_update_current_user_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
        ]);

        $response->assertNoContent();
    }

    public function test_update_current_user_with_password_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $oldPassword = $user->password;
        $response = $this->postJson('/api/profile', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertNoContent();
        $user->refresh();

        $this->assertNotSame($oldPassword, $user->password);
    }

    public function test_update_current_user_with_password_fail_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
            'password' => '12345',
            'password_confirmation' => '123451',
        ]);

        $response->assertUnprocessable();
    }

    public function test_update_current_user_with_email_fail_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'email' => 'newexample.com',
            'name' => 'Test Guy',
        ]);

        $response->assertUnprocessable();
    }

    public function test_update_current_user_settings_response(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/profile', [
            'email' => 'new@example.com',
            'name' => 'Test Guy',
            'settings' => [
                'language' => 'en',
                'theme' => 'dark',
                'unsupported' => 'test',
            ],
        ]);

        $response->assertNoContent();
    }
}
