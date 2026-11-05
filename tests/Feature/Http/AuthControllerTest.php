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

    public function test_reset_password_preserves_email_verification_and_allows_login(): void
    {
        Config::set('bar-assistant.mail_require_confirmation', true);
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'test@test.com',
            'password' => 'new_password_1234',
            'password_confirmation' => 'new_password_1234'
        ]);

        $response->assertNoContent();

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        $this->postJson('/api/auth/login', [
            'email' => 'test@test.com',
            'password' => 'new_password_1234',
        ])->assertOk();
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

    public function test_login_token_expires_after_default_14_days(): void
    {
        $this->setAuthExpirationEnv(null);

        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'my-test-password'
        ]);

        $response->assertOk();

        /** @var \Laravel\Sanctum\PersonalAccessToken */
        $token = $user->tokens()->latest('id')->first();

        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->between(
            now()->addDays(14)->subMinutes(2),
            now()->addDays(14)->addMinutes(2)
        ));
    }

    public function test_login_token_expires_after_configured_days(): void
    {
        Config::set('bar-assistant.auth.token_expiration_days', 90);

        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'my-test-password'
        ]);

        $response->assertOk();

        /** @var \Laravel\Sanctum\PersonalAccessToken */
        $token = $user->tokens()->latest('id')->first();

        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->between(
            now()->addDays(90)->subMinutes(2),
            now()->addDays(90)->addMinutes(2)
        ));
    }

    public function test_login_token_expiration_falls_back_to_default_on_unparseable_value(): void
    {
        $this->setAuthExpirationEnv('abc');

        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'my-test-password'
        ]);

        $response->assertOk();

        /** @var \Laravel\Sanctum\PersonalAccessToken */
        $token = $user->tokens()->latest('id')->first();

        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->between(
            now()->addDays(14)->subMinutes(2),
            now()->addDays(14)->addMinutes(2)
        ));

        $this->getJson('/api/profile', ['Authorization' => 'Bearer ' . $response['data']['token']])->assertOk();
    }

    public function test_login_token_never_expires_when_configured_zero_or_negative(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => Hash::make('my-test-password'),
        ]);

        $tokens = [];
        foreach ([0, -1] as $days) {
            Config::set('bar-assistant.auth.token_expiration_days', $days);

            $response = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'my-test-password'
            ]);

            $response->assertOk();

            /** @var \Laravel\Sanctum\PersonalAccessToken */
            $token = $user->tokens()->latest('id')->first();
            $this->assertNull($token->expires_at);

            $tokens[] = $response['data']['token'];
        }

        // Non-expiring tokens remain usable well past the default 14 days
        $this->travel(20)->days();

        foreach ($tokens as $token) {
            $this->getJson('/api/profile', ['Authorization' => 'Bearer ' . $token])->assertOk();
        }
    }

    /**
     * Set AUTH_TOKEN_EXPIRATION_DAYS in the environment (null unsets it) and
     * re-evaluate the bar-assistant config file, mimicking an app boot.
     */
    private function setAuthExpirationEnv(?string $value): void
    {
        if ($value === null) {
            putenv('AUTH_TOKEN_EXPIRATION_DAYS');
            unset($_ENV['AUTH_TOKEN_EXPIRATION_DAYS'], $_SERVER['AUTH_TOKEN_EXPIRATION_DAYS']);
        } else {
            putenv('AUTH_TOKEN_EXPIRATION_DAYS=' . $value);
            $_ENV['AUTH_TOKEN_EXPIRATION_DAYS'] = $value;
            $_SERVER['AUTH_TOKEN_EXPIRATION_DAYS'] = $value;
        }

        config()->set('bar-assistant', require config_path('bar-assistant.php'));
    }

    protected function tearDown(): void
    {
        putenv('AUTH_TOKEN_EXPIRATION_DAYS');
        unset($_ENV['AUTH_TOKEN_EXPIRATION_DAYS'], $_SERVER['AUTH_TOKEN_EXPIRATION_DAYS']);

        parent::tearDown();
    }
}
