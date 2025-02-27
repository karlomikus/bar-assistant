<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Kami\Cocktail\Services\SSO\Providers;
use Kami\Cocktail\Services\SSO\SSOService;
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\ValueObjects\SSOProvider;
use Kami\Cocktail\Http\Resources\SSOProviderResource;

class SSOAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $validProvider = Providers::tryFrom($provider);
        if ($validProvider === null) {
            abort(404, 'Unsupported provider');
        }

        /** @phpstan-ignore-next-line */
        return Socialite::driver($validProvider->value)->stateless()->redirect();
    }

    #[OAT\Get(path: '/auth/sso/{provider}/callback', tags: ['Authentication'], operationId: 'ssoCallback', description: 'Callback for SSO login', summary: 'SSO callback', parameters: [
        new OAT\Parameter(name: 'provider', in: 'path', required: true, description: 'Provider ID', schema: new OAT\Schema(type: 'string')),
        new OAT\Parameter(name: 'code', in: 'query', required: true, description: 'Oauth token', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Token::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function callback(string $provider, SSOService $ssoService): TokenResource
    {
        $validProvider = Providers::tryFrom($provider);
        if ($validProvider === null) {
            abort(400, 'Unsupported provider');
        }

        try {
            /** @phpstan-ignore-next-line */
            $socialiteUser = Socialite::driver($validProvider->value)->stateless()->user() ?? throw new \Exception('Failed to get user');
        } catch (\Exception $e) {
            abort(403, $e->getMessage());
        }

        $credentials = $ssoService->findOrCreateCredential($socialiteUser, $validProvider);
        $token = $credentials->user->createToken('SSO Login via: ' . $validProvider->value, expiresAt: now()->addDays(14));

        return new TokenResource($token);
    }

    #[OAT\Get(path: '/auth/sso/providers', tags: ['Authentication'], operationId: 'ssoProviders', description: 'Configured SSO providers', summary: 'SSO providers', security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(SSOProviderResource::class),
    ])]
    public function list(): JsonResource
    {
        $providers = Providers::cases();

        $enabledProviders = [];
        foreach ($providers as $provider) {
            $enabledProviders[] = new SSOProvider(
                $provider,
                $provider->getPrettyName(),
                !blank(config("services.{$provider->value}.client_id")),
            );
        }

        return SSOProviderResource::collection($enabledProviders);
    }
}
