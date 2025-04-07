<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Support\Str;
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
    #[OAT\Get(path: '/auth/sso/{provider}/redirect', tags: ['Authentication'], operationId: 'ssoRedirect', description: 'Redirect to SSO authentication', summary: 'SSO redirect', parameters: [
        new OAT\Parameter(name: 'provider', in: 'path', required: true, description: 'Provider ID', schema: new OAT\Schema(ref: OauthProvider::class)),
    ], security: [])]
    #[OAT\Response(response: 302, description: 'Redirect response')]
    #[BAO\NotFoundResponse]
    public function redirect(string $provider): RedirectResponse
    {
        $validProvider = OauthProvider::tryFrom($provider);
        if ($validProvider === null || !OauthCredential::isProviderConfigured($validProvider)) {
            abort(404, 'Unsupported provider');
        }

        /** @var \Laravel\Socialite\Two\AbstractProvider */
        $driver = Socialite::driver($validProvider->value);

        // Authelia always requires a state parameter to be set
        if ($validProvider === OauthProvider::Authelia) {
            $driver->with(['state' => Str::random(40)]);
        }

        return $driver->stateless()->redirect();
    }

    #[OAT\Get(path: '/auth/sso/{provider}/callback', tags: ['Authentication'], operationId: 'ssoCallback', description: 'Callback for SSO login', summary: 'SSO callback', parameters: [
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
        $validProvider = OauthProvider::tryFrom($provider);
        if ($validProvider === null || !OauthCredential::isProviderConfigured($validProvider)) {
            abort(400, 'Unsupported provider');
        }

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider */
            $driver = Socialite::driver($validProvider->value);

            $socialiteUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            abort(403, $e->getMessage());
        }

        try {
            $credentials = $ssoService->findOrCreateCredential($socialiteUser, $validProvider);
        } catch (\Exception $e) {
            Log::error('Unable to create SSO credentials for user id: ' . $socialiteUser->getId() . ' provider: ' . $validProvider->value . ' error: ' . $e->getMessage());
            abort(500, 'Unable to create SSO credentials');
        }

        Log::info('Created SSO credentials for user id: ' . $credentials->user->id);

        $token = $credentials->user->createToken('SSO Login via: ' . $validProvider->value, expiresAt: now()->addDays(14));

        return new TokenResource($token);
    }

    #[OAT\Get(path: '/auth/sso/providers', tags: ['Authentication'], operationId: 'ssoProviders', description: 'Configured SSO providers', summary: 'SSO providers', security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(SSOProviderResource::class),
    ])]
    public function list(): JsonResource
    {
        $enabledProviders = OauthCredential::getAvailableProviders();

        return SSOProviderResource::collection($enabledProviders);
    }
}
