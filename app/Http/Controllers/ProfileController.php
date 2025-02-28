<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Services\Auth\OauthProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ProfileResource;
use Kami\Cocktail\Http\Requests\UpdateUserRequest;

class ProfileController extends Controller
{
    #[OAT\Get(path: '/profile', tags: ['Profile'], operationId: 'showProfile', description: 'Show current user profile information', summary: 'Show profile')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Profile::class),
    ])]
    #[BAO\NotFoundResponse]
    public function show(Request $request): JsonResource
    {
        $user = $request->user();
        $user->load([
            'memberships' => fn ($memberships) => $memberships->chaperone(),
            'oauthCredentials',
        ]);

        return new ProfileResource($request->user());
    }

    #[OAT\Post(path: '/profile', tags: ['Profile'], operationId: 'updateProfile', description: 'Update user profile', summary: 'Update profile', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\ProfileRequest::class),
        ]
    ))]
    #[OAT\Response(response: 201, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Profile::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function update(UpdateUserRequest $request): JsonResource
    {
        $barId = $request->post('bar_id', null);
        $currentUser = $request->user();
        $currentUser->name = $request->post('name');
        $currentUser->email = $request->post('email');

        if ($request->has('password') && $request->post('password') !== null) {
            $currentUser->password = Hash::make($request->input('password'));

            $currentUser->tokens()->delete();
        }

        // If there is a bar context
        if ($barId !== null) {
            $barMembership = $currentUser->getBarMembership((int) $barId);
            if ($barMembership) {
                $barMembership->is_shelf_public = (bool) $request->post('is_shelf_public');
                $barMembership->save();
            }
        }

        $currentUser->save();

        return new ProfileResource($request->user());
    }

    #[OAT\Delete(path: '/profile/sso/{provider}', tags: ['Profile'], operationId: 'deleteSSO', description: 'Delete user\'s SSO provider', summary: 'Delete SSO provider', parameters: [
        new OAT\Parameter(name: 'provider', in: 'path', required: true, description: 'Provider ID', schema: new OAT\Schema(ref: OauthProvider::class)),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function deleteSSOProvider(Request $request, string $provider): Response
    {
        $validProvider = OauthProvider::tryFrom($provider);
        if ($validProvider === null) {
            abort(404, 'Unsupported provider');
        }

        $request->user()->oauthCredentials()->where('provider', $validProvider->value)->delete();

        return new Response(null, 204);
    }
}
