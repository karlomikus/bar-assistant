<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Mockery;
use Tests\TestCase;
use Kami\Cocktail\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Models\UserOAuthAccount;
use Kami\Cocktail\OAuth\OAuthProvider;
use Kami\Cocktail\OAuth\OAuthService;
use Kami\Cocktail\OAuth\OAuthUtils;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

class OAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_user_login_creates_new_user(): void
    {
        $accessToken = 'mocked-access-token';
        $email = 'test@example.com';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'sub' => $providerUserId,
                'email' => $email,
                'preferred_username' => $name
            ]);
        $mockResourceOwner
            ->shouldReceive('getId')
            ->andReturn($providerUserId);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($mockResourceOwner);

        $provider = $this->getProvider();
        $oauthService = new OAuthService($provider);

        $user = $oauthService->handleUserLogin($provider->id, $accessToken);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => $name,
        ]);

        $this->assertDatabaseHas('user_oauth_accounts', [
            'provider_user_id' => $providerUserId,
            'provider_id' => $provider->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_handle_user_login_updates_existing_user(): void
    {
        $email = 'test@example.com';
        $accessToken = 'mocked-access-token';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';

        $user = User::factory()->create([
            'email' => $email,
            'name' => $name,
        ]);

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'sub' => $providerUserId,
                'email' => $email,
                'preferred_username' => $name
            ]);
        $mockResourceOwner
            ->shouldReceive('getId')
            ->andReturn($providerUserId);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($mockResourceOwner);

        $provider = $this->getProvider();
        $oauthService = new OAuthService($provider);

        $userFromLogin = $oauthService->handleUserLogin($provider->id, $accessToken);

        $this->assertEquals($user->id, $userFromLogin->id);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => $name,
        ]);

        $this->assertDatabaseHas('user_oauth_accounts', [
            'provider_user_id' => $providerUserId,
            'provider_id' => $provider->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_handle_user_login_updates_existing_link(): void
    {
        $email = 'test@example.com';
        $accessToken = 'mocked-access-token';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';
        $provider = $this->getProvider();

        $user = User::factory()->create([
            'email' => $email,
            'name' => $name,
        ]);

        $oauthAccount = UserOAuthAccount::factory()->create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'provider_user_id' => 'SOMETHING_ELSE',
        ]);

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'sub' => $providerUserId,
                'email' => $email,
                'preferred_username' => $name
            ]);
        $mockResourceOwner
            ->shouldReceive('getId')
            ->andReturn($providerUserId);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($mockResourceOwner);

        $oauthService = new OAuthService($provider);

        $userFromLogin = $oauthService->handleUserLogin($provider->id, $accessToken);

        $this->assertEquals($user->id, $userFromLogin->id);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => $name,
        ]);

        $this->assertDatabaseHas('user_oauth_accounts', [
            'id' => $oauthAccount->id,
            'provider_user_id' => $providerUserId,
            'provider_id' => $provider->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_handle_user_login_throws_when_email_not_provided(): void
    {
        $accessToken = 'mocked-access-token';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';
        $provider = $this->getProvider();

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'sub' => $providerUserId,
                'preferred_username' => $name
            ]);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);
        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($mockResourceOwner);

        $oauthService = new OAuthService($provider);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email not provided by provider');

        $oauthService->handleUserLogin($provider->id, $accessToken);
    }

    public function test_get_user_success(): void
    {
        $provider = $this->getProvider();
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';
        $email = 'test@example.com';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';

        $user = User::factory()->create([
            'email' => $email,
            'name' => $name,
        ]);

        UserOAuthAccount::factory()->create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'provider_user_id' => $providerUserId,
        ]);

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'sub' => $providerUserId,
                'email' => $email,
                'preferred_username' => $name
            ]);
        $mockResourceOwner
            ->shouldReceive('getId')
            ->andReturn($providerUserId);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($mockResourceOwner);

        $provider = $this->getProvider();
        $oauthService = new OAuthService($provider);

        $userFromGetUser = $oauthService->getUser($accessToken, $refreshToken);

        $this->assertEquals($user->id, $userFromGetUser->id);
    }

    public function test_get_user_tries_refresh_token_success(): void
    {
        $provider = $this->getProvider();
        $accessToken = 'mocked-access-token';
        $newAccessToken = 'mocked-new-access-token';
        $newAccessTokenObject = new AccessToken(['access_token' => $newAccessToken]);
        $refreshToken = 'mocked-refresh-token';
        $email = 'test@example.com';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';

        $user = User::factory()->create([
            'email' => $email,
            'name' => $name,
        ]);

        UserOAuthAccount::factory()->create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'provider_user_id' => $providerUserId,
        ]);

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'sub' => $providerUserId,
                'email' => $email,
                'preferred_username' => $name
            ]);
        $mockResourceOwner
            ->shouldReceive('getId')
            ->andReturn($providerUserId);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andThrow(new \Exception('Failed to fetch resource owner'))
            ->once();

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($newAccessToken)
            ->andReturn($mockResourceOwner)
            ->once();

        $mockGenericProvider->shouldReceive('getAccessToken')
            ->with('refresh_token', ['refresh_token' => $refreshToken])
            ->andReturn($newAccessTokenObject)
            ->once();

        $mockOAuthUtils = Mockery::mock('overload:' . OAuthUtils::class);
        $mockOAuthUtils->shouldReceive('setOAuthCookies')
            ->with($provider->id, $newAccessTokenObject)
            ->once();

        $provider = $this->getProvider();
        $oauthService = new OAuthService($provider);

        $userFromGetUser = $oauthService->getUser($accessToken, $refreshToken);

        $this->assertEquals($user->id, $userFromGetUser->id);
    }

    public function test_get_user_provider_user_id_fails(): void
    {
        $provider = $this->getProvider();
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';
        $email = 'test@example.com';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';

        $user = User::factory()->create([
            'email' => $email,
            'name' => $name,
        ]);

        UserOAuthAccount::factory()->create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'provider_user_id' => $providerUserId,
        ]);

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'id' => $providerUserId,
                'email' => $email,
                'preferred_username' => $name
            ]);
        $mockResourceOwner
            ->shouldReceive('getId')
            ->andReturn($providerUserId);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($mockResourceOwner);

        $provider = $this->getProvider();
        $oauthService = new OAuthService($provider);


        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve user ID from provider');

        $oauthService->getUser($accessToken, $refreshToken);
    }

    public function test_get_user_user_oauth_account_not_found(): void
    {
        $provider = $this->getProvider();
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';
        $email = 'test@example.com';
        $name = 'Test User';
        $providerUserId = 'mocked-provider-user-id';

        $mockResourceOwner = Mockery::mock();
        $mockResourceOwner
            ->shouldReceive('toArray')
            ->andReturn([
                'sub' => $providerUserId,
                'email' => $email,
                'preferred_username' => $name
            ]);
        $mockResourceOwner
            ->shouldReceive('getId')
            ->andReturn($providerUserId);

        $mockGenericProvider = Mockery::mock('overload:' . GenericProvider::class);

        $mockGenericProvider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($mockResourceOwner);

        $provider = $this->getProvider();
        $oauthService = new OAuthService($provider);


        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found');

        $oauthService->getUser($accessToken, $refreshToken);
    }

    protected function getProvider()
    {
        $provider = OAuthProvider::fromArray([
            'id' => 'google',
            'clientId' => 'google',
            'clientSecret' => 'secret',
            'name' => 'Google',
            'authority' => 'https://accounts.google.com',
            'authorizationEndpoint' => 'https://accounts.google.com/authorize',
            'tokenEndpoint' => 'https://accounts.google.com/token',
            'userInfoEndpoint' => 'https://accounts.google.com/user',
            'scope' => 'openid email profile',
        ], 'http://localhost/login/callback');

        return $provider;
    }
}
