<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Services\Auth\OauthProvider;
use Kami\Cocktail\Services\Auth\SSOService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SSOServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_new_credentials(): void
    {
        /** @var SSOService $service */
        $service = resolve(SSOService::class);

        $ssoUser = $this->getDummyUser();

        $credentials = $service->findOrCreateCredential($ssoUser, OauthProvider::Authentik);

        $this->assertSame(1, $credentials->user->id);
        $this->assertSame('dummy', $credentials->user->name);
        $this->assertSame('dummy@example.com', $credentials->user->email);
        $this->assertSame('authentik', $credentials->provider);
        $this->assertSame(801, $credentials->provider_id);
        $this->assertSame(1, $credentials->user_id);
    }

    public function test_creates_credentials_for_existing_user(): void
    {
        /** @var SSOService $service */
        $service = resolve(SSOService::class);

        $user = User::factory()->create(['id' => 7, 'email' => 'dummy@example.com', 'name' => 'Existing name']);
        $ssoUser = $this->getDummyUser();

        $credentials = $service->findOrCreateCredential($ssoUser, OauthProvider::Authentik);

        $this->assertSame(7, $credentials->user->id);
        $this->assertSame('Existing name', $credentials->user->name);
        $this->assertSame('dummy@example.com', $credentials->user->email);
        $this->assertSame('authentik', $credentials->provider);
        $this->assertSame(801, $credentials->provider_id);
        $this->assertSame(7, $credentials->user_id);
        $this->assertCount(1, $user->oauthCredentials);
    }

    private function getDummyUser(): SocialiteUser
    {
        return new class implements SocialiteUser {
            public function getId() {
                return 801;
            }
            public function getNickname() {
                return 'dummy';
            }
            public function getName() {
                return 'Dummy User';
            }
            public function getEmail() {
                return 'dummy@example.com';
            }
            public function getAvatar() {
                return 'https://example.com/avatar.png';
            }
        };
    }
}
