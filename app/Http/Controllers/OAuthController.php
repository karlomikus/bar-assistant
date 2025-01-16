<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\UserOAuthAccount;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OAuth\OAuthService;
use Kami\Cocktail\OAuth\OAuthUtils;

class OAuthController extends Controller
{
    #[OAT\Post(path: '/oauth/login', tags: ['OAuth'], operationId: 'oauth-login', summary: 'Authenticate user with OAuth', description: 'Authenticate user with OAuth login and get auth token', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\OAuthLoginRequest::class),
        ]
    ), security: [])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[OAT\Response(response: 400, description: 'Unable to authenticate')]
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required'],
            'codeVerifier' => ['required'],
            'providerId' => ['required'],
        ]);

        $code = $request->input('code');
        $codeVerifier = $request->input('codeVerifier');
        $providerId = $request->input('providerId');

        $providers = config('bar-assistant.oauth_login_providers');

        $providerConfig = collect($providers)
            ->first(fn($provider) => $provider->id === $providerId);

        if (!isset($providerConfig)) {
            abort(400, 'Invalid provider');
        }

        try {
            $oauthService = new OAuthService($providerConfig);
            $accessToken = $oauthService->getAccessToken($code, $codeVerifier);
            $oauthService->handleUserLogin($providerId, $accessToken);
            OAuthUtils::setOAuthCookies($providerId, $accessToken);
            return response()->json(status: 204);
        } catch (\Throwable $e) {
            abort(401, $e->getMessage());
        }
    }

    #[OAT\Get(path: '/oauth/accounts', tags: ['OAuth'], operationId: 'listOAuthAccounts', description: 'Show list of all user OAuth accounts', summary: 'List OAuth accounts')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapItemsWithData(BAO\Schemas\UserOAuthAccount::class),
    ])]
    public function accounts(Request $request): JsonResource
    {
        $rawAccounts = $request->user()->oauthAccounts()->get();
        $providers = config('bar-assistant.oauth_login_providers');

        $providerMap = [];
        foreach ($providers as $provider) {
            $providerMap[$provider->id][] = $provider;
        }

        $accounts = $rawAccounts->map(function ($account) use ($providerMap) {
            $provider = $providerMap[$account->provider_id][0];
            return [
                'id' => $account->id,
                'icon' => $provider->icon,
                'name' => $provider->name,
                'userId' => $account->provider_user_id,
                'createdAt' => $account->created_at->toAtomString(),
            ];
        });

        return new JsonResource(['data' => $accounts]);
    }

    #[OAT\Delete(path: '/oauth/accounts/{id}', tags: ['OAuth'], operationId: 'unlinkOAuthAccount', description: 'Unlink an OAuth account', summary: 'Unlink OAuth Account', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function unlinkAccount(Request $request, int $id): Response
    {
        $account = UserOAuthAccount::findOrFail($id);

        if ($request->user()->cannot('delete', $account)) {
            // 403 would give away that the account exists
            // and allow enumeration
            abort(404);
        }

        $account->delete();

        return new Response(null, 204);
    }
}
