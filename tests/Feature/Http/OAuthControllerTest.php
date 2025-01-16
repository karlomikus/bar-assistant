<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Mockery;
use Tests\TestCase;
use Kami\Cocktail\OAuth\OAuthService;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Illuminate\Support\Facades\Config;
use Kami\Cocktail\OAuth\OAuthCookie;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\Models\UserOAuthAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OAuthControllerTest extends TestCase
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

    public function test_login_succesful(): void
    {
        // arrange
        $providerId = 'google';
        $code = 'code';
        $codeVerifier = 'code-verifier';
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';

        // mocks
        $mockAccessToken = Mockery::mock('alias:League\OAuth2\Client\Token\AccessTokenInterface');
        $mockAccessToken->shouldReceive('getToken')->once()->andReturn($accessToken);
        $mockAccessToken->shouldReceive('getExpires')->once()->andReturn(1);
        $mockAccessToken->shouldReceive('getRefreshToken')->once()->andReturn($refreshToken);

        $mockService = Mockery::mock('overload:' . OAuthService::class);
        $mockService->shouldReceive('getAccessToken')
            ->with($code, $codeVerifier)
            ->once()
            ->andReturn($mockAccessToken);
        $mockService->shouldReceive('handleUserLogin')
            ->with($providerId, $mockAccessToken)
            ->once();

        // act
        $response = $this->postJson('/api/oauth/login', [
            'providerId' => $providerId,
            'code' => $code,
            'codeVerifier' => $codeVerifier,
        ]);

        // assert
        $response->assertStatus(204);

        $cookies = collect($response->headers->getCookies())->groupBy(fn($cookie) => $cookie->getName());
        $this->assertEquals($providerId, $cookies->get(OAuthCookie::OAUTH_PROVIDER_ID)[0]->getValue());
        $this->assertEquals($accessToken, $cookies->get(OAuthCookie::OAUTH_ACCESS_TOKEN)[0]->getValue());
        $this->assertEquals($refreshToken, $cookies->get(OAuthCookie::OAUTH_REFRESH_TOKEN)[0]->getValue());
    }

    public function test_login_bad_provider(): void
    {
        $response = $this->postJson('/api/oauth/login', [
            'providerId' => 'provider-that-doesnt-exist',
            'code' => 'code',
            'codeVerifier' => 'code-verifier',
        ]);

        $response->assertBadRequest();
    }

    public function test_login_bad_code(): void
    {
        $code = 'bad-code-response';
        $codeVerifier = 'bad-code-verifier';

        Mockery::mock('overload:' . OAuthService::class)
            ->shouldReceive('getAccessToken')
            ->with($code, $codeVerifier)
            ->andThrow(new IdentityProviderException('Invalid token', 400, null));

        $response = $this->postJson('/api/oauth/login', [
            'providerId' => 'google',
            'code' => $code,
            'codeVerifier' => $codeVerifier,
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_disabled(): void
    {
        Config::set('bar-assistant.oauth_login_enabled', false);

        $providerId = 'google';
        $code = 'code';
        $codeVerifier = 'code-verifier';

        $response = $this->postJson('/api/oauth/login', [
            'providerId' => $providerId,
            'code' => $code,
            'codeVerifier' => $codeVerifier,
        ]);

        $response->assertForbidden();
    }

    public function test_get_accounts(): void
    {
        $setup = $this->setupOAuthAccounts();

        $user = $setup['user1'];
        $this->actingAs($user);

        $user1Account1 = $setup['user1Account1'];
        $user1Account2 = $setup['user1Account2'];

        $response = $this->getJson('/api/oauth/accounts');

        $response->assertOk();

        $accounts = $response->json();
        $expectedAccountIds = [$user1Account1->id, $user1Account2->id];
        $actualAccountIds = array_column($accounts['data'], 'id');
        $this->assertEqualsCanonicalizing(
            $expectedAccountIds,
            $actualAccountIds,
            'Response should only contain user1 accounts.'
        );
    }

    public function test_unlink_account_succesful(): void
    {
        $setup = $this->setupOAuthAccounts();

        $user = $setup['user1'];
        $this->actingAs($user);

        $user1Account1 = $setup['user1Account1'];

        $response = $this->deleteJson("/api/oauth/accounts/{$user1Account1->id}");

        $response->assertNoContent();
    }

    public function test_unlink_account_forbidden(): void
    {
        $setup = $this->setupOAuthAccounts();

        $user = $setup['user1'];
        $this->actingAs($user);

        $user2Account1 = $setup['user2Account1'];

        $response = $this->deleteJson("/api/oauth/accounts/{$user2Account1->id}");

        $response->assertNotFound();
    }

    public function test_unlink_account_not_found(): void
    {
        $setup = $this->setupOAuthAccounts();

        $user = $setup['user1'];
        $this->actingAs($user);


        $response = $this->deleteJson("/api/oauth/accounts/999");

        $response->assertNotFound();
    }

    public function setupOAuthAccounts(): array
    {
        $user1 = User::factory()->create();
        $user1Account1 = UserOAuthAccount::factory()->create([
            'user_id' => $user1->id,
            'provider_id' => 'google',
        ]);
        $user1Account2 = UserOAuthAccount::factory()->create([
            'user_id' => $user1->id,
            'provider_id' => 'keycloak',
        ]);

        $user2 = User::factory()->create();
        $user2Account1 = UserOAuthAccount::factory()->create([
            'user_id' => $user2->id,
            'provider_id' => 'google',
        ]);

        return [
            'user1' => $user1,
            'user1Account1' => $user1Account1,
            'user1Account2' => $user1Account2,
            'user2' => $user2,
            'user2Account1' => $user2Account1,
        ];
    }
}
