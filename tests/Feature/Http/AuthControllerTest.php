<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Illuminate\Support\Str;
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
        $response = $this->postJson('/api/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.name', 'Test Guy');
        $response->assertJsonPath('data.email', 'test@test.com');
    }

    public function test_register_response_sends_confirm_email(): void
    {
        Mail::fake();
        Config::set('bar-assistant.mail_require_confirmation', true);

        $this->postJson('/api/register', [
            'email' => 'test@test.com',
            'password' => 'test-password',
            'name' => 'Test Guy',
        ]);

        Mail::assertQueued(ConfirmAccount::class, function (ConfirmAccount $mail) {
            return $mail->hasTo('test@test.com');
        });
    }

    public function test_forgot_password_response(): void
    {
        Mail::fake();
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@test.com'
        ]);

        Mail::assertQueued(PasswordReset::class, function (PasswordReset $mail) {
            return $mail->hasTo('test@test.com');
        });

        $response->assertSuccessful();
    }

    public function test_forgot_password_unknown_email_response(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'a@test.com'
        ]);

        Mail::assertNotQueued(PasswordReset::class);

        $response->assertBadRequest();
    }

    public function test_reset_password_response(): void
    {
        $oldPassword = Hash::make('my-test-password');
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => $oldPassword,
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
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

        $response = $this->postJson('/api/reset-password', [
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

        $response = $this->getJson('/api/verify/' . $user->id . '/' . $hash);

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $response->assertSuccessful();
    }
}
