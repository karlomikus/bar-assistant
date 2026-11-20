<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Throwable;
use Illuminate\Support\Str;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Kami\Cocktail\Models\OauthCredential;
use Kami\Cocktail\Services\Auth\SSOService;
use Kami\Cocktail\Services\Auth\OauthProvider;
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\SSOProviderResource;

class SSOAuthController extends Controller
{
    #[OAT\Get(path: '/auth/sso/{provider}/redirect', tags: ['Authentication'], operationId: 'ssoRedirect', description: 'Redirect to SSO authentication.', summary: 'SSO redirect', parameters: [
        new OAT\Parameter(name: 'provider', in: 'path', required: true, description: 'Provider ID', schema: new OAT\Schema(ref: OauthProvider::class)),
    ], security: [])]
    #[OAT\Response(response: 302, description: 'Redirect response', headers: [
        new OAT\Header(header: 'Location', description: 'SSO provider authorization URL', schema: new OAT\Schema(type: 'string', format: 'uri')),
    ])]
    #[BAO\NotFoundResponse]
    public function redirect(string $provider): RedirectResponse
    {
        $validProvider = $this->resolveConfiguredProvider($provider);

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($validProvider->value);

        // Authelia and Pocket ID (>= 2.10) require a state parameter
        if ($validProvider === OauthProvider::Authelia || $validProvider === OauthProvider::PocketId) {
            $driver->with(['state' => Str::random(40)]);
        }

        return $driver->stateless()->redirect();
    }

    #[OAT\Get(path: '/auth/sso/{provider}/callback', tags: ['Authentication'], operationId: 'ssoCallback', description: 'Callback for SSO login.', summary: 'SSO callback', parameters: [
        new OAT\Parameter(name: 'provider', in: 'path', required: true, description: 'Provider ID', schema: new OAT\Schema(ref: OauthProvider::class)),
        new OAT\Parameter(name: 'code', in: 'query', required: true, description: 'Oauth token', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(TokenResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function callback(string $provider, SSOService $ssoService): TokenResource
    {
        $validProvider = $this->resolveConfiguredProvider($provider);

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($validProvider->value);
            $driver->stateless();
            $socialiteUser = $driver->user();
        } catch (Throwable $e) {
            Log::warning('SSO authentication failed', [
                'provider' => $validProvider->value,
                'error' => $e->getMessage(),
            ]);
            abort(403);
        }

        try {
            $credentials = $ssoService->findOrCreateCredential($socialiteUser, $validProvider);
        } catch (Throwable $e) {
            Log::error('Unable to create SSO credentials for user id: ' . $socialiteUser->getId() . ' provider: ' . $validProvider->value . ' error: ' . $e->getMessage());
            abort(500, 'Unable to create SSO credentials');
        }

        $user = $credentials->user;
        if (!$user instanceof User) {
            Log::error('Unable to resolve user for SSO credentials', [
                'credential_id' => $credentials->id,
                'provider' => $validProvider->value,
            ]);
            abort(500, 'Unable to create SSO credentials');
        }

        if ($user->isAnonymized()) {
            abort(403);
        }

        Log::info('Authenticated user via SSO', [
            'user_id' => $user->id,
            'provider' => $validProvider->value,
        ]);

        /** @var int $days */
        $days = config('bar-assistant.auth.token_expiration_days');
        $expiresAt = $days > 0 ? now()->addDays($days) : null;
        $token = $user->createToken('SSO Login via: ' . $validProvider->value, expiresAt: $expiresAt);

        return new TokenResource($token);
    }

    #[OAT\Get(path: '/auth/sso/providers', tags: ['Authentication'], operationId: 'ssoProviders', description: 'List of server supported and configured SSO providers.', summary: 'SSO providers', security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(SSOProviderResource::class),
    ])]
    public function list(): JsonResource
    {
        $enabledProviders = OauthCredential::getAvailableProviders();

        return SSOProviderResource::collection($enabledProviders);
    }

    private function resolveConfiguredProvider(string $provider): OauthProvider
    {
        $validProvider = OauthProvider::tryFrom($provider);
        if ($validProvider === null || !OauthCredential::isProviderConfigured($validProvider)) {
            abort(404, 'Unsupported provider');
        }

        return $validProvider;
    }
}
