<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\PasswordReset;
use Illuminate\Support\Facades\Config;
use Kami\Cocktail\Mail\ConfirmAccount;
use Illuminate\Support\Facades\Password;
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

        $response = $this->postJson('/api/auth/login', [
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

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@test2.com',
            'password' => 'my-test-password'
        ]);

        $response->assertBadRequest();
    }

    public function test_logout_response(): void
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        // Logout and check headers
        $response = $this->postJson('/api/auth/logout');

        $response->assertNoContent();
    }

    public function test_register_response(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        $response->assertCreated();
        $response->assertHeader('Location');
    }

    public function test_register_response_sends_confirm_email(): void
    {
        Mail::fake();
        Config::set('bar-assistant.mail_require_confirmation', true);

        $this->postJson('/api/auth/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        Mail::assertQueued(ConfirmAccount::class, fn (ConfirmAccount $mail) => $mail->hasTo('test@test.com'));
    }

    public function test_forgot_password_response(): void
    {
        Mail::fake();
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@test.com'
        ]);

        Mail::assertQueued(PasswordReset::class, fn (PasswordReset $mail) => $mail->hasTo('test@test.com'));

        $response->assertSuccessful();
    }

    public function test_forgot_password_unknown_email_response(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'a@test.com'
        ]);

        Mail::assertNotQueued(PasswordReset::class);

        $response->assertNoContent();
    }

    public function test_reset_password_response(): void
    {
        $oldPassword = Hash::make('my-test-password');
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => $oldPassword,
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'test@test.com',
            'password' => 'new_password_1234',
            'password_confirmation' => 'new_password_1234'
        ]);

        $user->refresh();

        $this->assertNotSame($user->password, $oldPassword);
        $response->assertSuccessful();
    }

    public function test_reset_password_wrong_token_response(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);
        Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => Str::random(8),
            'email' => 'test@test.com',
            'password' => 'new_password_1234',
            'password_confirmation' => 'new_password_1234'
        ]);

        $response->assertBadRequest();
    }

    public function test_confirm_account_response(): void
    {
        Config::set('bar-assistant.mail_require_confirmation', true);

        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
            'email_verified_at' => null,
        ]);
        $hash = sha1('test@test.com');

        $response = $this->getJson('/api/auth/verify/' . $user->id . '/' . $hash);

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $response->assertSuccessful();
    }

    public function test_login_requires_confirmation(): void
    {
        Config::set('bar-assistant.mail_require_confirmation', true);

        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'my-test-password'
        ]);

        $response->assertStatus(400);
    }

    public function test_register_rate_limiting(): void
    {
        // Send 5 requests - all should succeed (within limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/register', [
                'email' => 'test' . $i . '@test.com',
                'password' => 'test-password',
                'name' => 'Test Guy ' . $i,
            ]);
            $response->assertSuccessful();
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/auth/register', [
            'email' => 'overflow@test.com',
            'password' => 'test-password',
            'name' => 'Overflow Guy',
        ]);

        $response->assertTooManyRequests();
    }

    public function test_login_rate_limiting(): void
    {
        $payload = [
            'email' => 'test@test.com',
            'password' => 'wrong-password',
        ];

        // Send 10 requests - all should respond (within limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', $payload);
            $response->assertStatus(400); // Bad credentials
        }

        // 11th request should be rate limited
        $response = $this->postJson('/api/auth/login', $payload);
        $response->assertTooManyRequests();
    }

    public function test_forgot_password_rate_limiting(): void
    {
        $payload = ['email' => 'test@test.com'];

        // Send 3 requests - all should respond (within limit)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/forgot-password', $payload);
            // Unknown email returns 400 but it's valid response (not rate limited yet)
            $this->assertLessThan(429, $response->status());
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/auth/forgot-password', $payload);
        $response->assertTooManyRequests();
    }
}
