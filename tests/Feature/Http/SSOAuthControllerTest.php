<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\TestCase;
use Laravel\Socialite\Two\User;
use Laravel\Socialite\Socialite;
use Illuminate\Support\Facades\Config;
use Kami\Cocktail\Models\User as AppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SSOAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_redirect_successful(): void
    {
        config()->set('services.github.client_id', 'fake-client-id');

        Socialite::fake('github');

        $response = $this->get('/api/auth/sso/github/redirect');

        $response->assertRedirect();
    }

    public function test_sso_redirect_unsuccessful_for_unconfigured_provider(): void
    {
        config()->set('services.github.client_id', 'fake-client-id');

        Socialite::fake('gitlab');

        $response = $this->get('/api/auth/sso/gitlab/redirect');

        $response->assertNotFound();
    }

    public function test_sso_callback(): void
    {
        config()->set('services.github.client_id', 'fake-client-id');

        Socialite::fake('github', new User()->map([
            'id' => 550,
            'name' => 'GitHub User',
            'email' => 'email@github.com',
        ]));

        $this->assertDatabaseEmpty('users');

        $response = $this->get('/api/auth/sso/github/callback');

        $this->assertDatabaseHas('users', ['email' => 'email@github.com', 'name' => 'GitHub User']);

        $response->assertOk();
        $this->assertNotNull($response['data']['token']);
    }

    public function test_sso_token_expires_after_configured_days(): void
    {
        Config::set('bar-assistant.auth.token_expiration_days', 90);
        config()->set('services.github.client_id', 'fake-client-id');

        Socialite::fake('github', new User()->map([
            'id' => 550,
            'name' => 'GitHub User',
            'email' => 'email@github.com',
        ]));

        $response = $this->get('/api/auth/sso/github/callback');

        $response->assertOk();

        $user = AppUser::where('email', 'email@github.com')->firstOrFail();

        /** @var \Laravel\Sanctum\PersonalAccessToken */
        $token = $user->tokens()->latest('id')->first();

        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->between(
            now()->addDays(90)->subMinutes(2),
            now()->addDays(90)->addMinutes(2)
        ));
    }

    public function test_sso_list(): void
    {
        config()->set('services.github.client_id', 'fake-client-id');

        $response = $this->get('/api/auth/sso/providers');

        foreach ($response['data'] as $provider) {
            if ($provider['name'] === 'github') {
                $this->assertTrue($provider['enabled']);
            } else {
                $this->assertFalse($provider['enabled']);
            }
        }
    }
}
