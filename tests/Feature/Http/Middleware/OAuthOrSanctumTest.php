<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Mockery;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Http\Middleware\OAuthOrSanctum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Kami\Cocktail\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\OAuth\OAuthService;

class OAuthOrSanctumTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sanctum_request(): void
    {
        $mockUser = Mockery::mock(User::class);

        Auth::shouldReceive('guard')
            ->with('sanctum')
            ->andReturnSelf();

        Auth::shouldReceive('check')
            ->andReturn(true);

        Auth::shouldReceive('user')
            ->andReturn($mockUser);

        Auth::shouldReceive('setUser')
            ->with($mockUser);

        $middleware = new OAuthOrSanctum();

        $request = Request::create('/test', 'GET');

        $next = function () {
            return new Response('OK', 200);
        };

        $response = $middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_oauth_request_succesful(): void
    {
        $provider = json_decode(json_encode([
            "id" => "google",
        ]));
        $providerId = 'google';
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';
        $mockUser = Mockery::mock(User::class);

        Config::set('bar-assistant.oauth_login_enabled', true);
        Config::set('bar-assistant.oauth_login_providers', [$provider]);

        $mockOAuthService = Mockery::mock('overload:' . OAuthService::class);

        $mockOAuthService
            ->shouldReceive('__construct')
            ->with($provider)
            ->once();

        $mockOAuthService
            ->shouldReceive('getUser')
            ->andReturn($mockUser);

        Auth::shouldReceive('setUser')
            ->with($mockUser);

        $next = function () {
            return new Response('OK', 200);
        };

        $middleware = new OAuthOrSanctum();

        $request = Request::create('/test', 'GET');
        $request->cookies->set('oauth_provider_id', $providerId);
        $request->cookies->set('access_token', $accessToken);
        $request->cookies->set('refresh_token', $refreshToken);

        $response = $middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_oauth_request_missing_provider_cookie(): void
    {
        $provider = json_decode(json_encode([
            "id" => "google",
        ]));
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';

        Config::set('bar-assistant.oauth_login_enabled', true);
        Config::set('bar-assistant.oauth_login_providers', [$provider]);

        $next = function () {
            return new Response('OK', 200);
        };

        $middleware = new OAuthOrSanctum();

        $request = Request::create('/test', 'GET');
        $request->cookies->set('access_token', $accessToken);
        $request->cookies->set('refresh_token', $refreshToken);

        $response = $middleware->handle($request, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_oauth_request_provider_not_found(): void
    {
        $provider = json_decode(json_encode([
            "id" => "google",
        ]));
        $providerId = 'non-existant';
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';

        Config::set('bar-assistant.oauth_login_enabled', true);
        Config::set('bar-assistant.oauth_login_providers', [$provider]);

        $next = function () {
            return new Response('OK', 200);
        };

        $middleware = new OAuthOrSanctum();

        $request = Request::create('/test', 'GET');
        $request->cookies->set('oauth_provider_id', $providerId);
        $request->cookies->set('access_token', $accessToken);
        $request->cookies->set('refresh_token', $refreshToken);

        $response = $middleware->handle($request, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_oauth_request_disabled(): void
    {
        $providerId = 'non-existant';
        $accessToken = 'mocked-access-token';
        $refreshToken = 'mocked-refresh-token';

        Config::set('bar-assistant.oauth_login_enabled', false);

        $next = function () {
            return new Response('OK', 200);
        };

        $middleware = new OAuthOrSanctum();

        $request = Request::create('/test', 'GET');
        $request->cookies->set('oauth_provider_id', $providerId);
        $request->cookies->set('access_token', $accessToken);
        $request->cookies->set('refresh_token', $refreshToken);

        $response = $middleware->handle($request, $next);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
