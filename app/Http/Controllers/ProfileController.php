<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Hash;
use BarAssistant\Application\User\UserService;
use Kami\Cocktail\Services\Auth\OauthProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ProfileResource;
use Kami\Cocktail\OpenAPI\Schemas\ProfileRequest;
use Kami\Cocktail\Http\Requests\UpdateUserRequest;
use Kami\Cocktail\Http\Requests\UpdatePasswordRequest;
use BarAssistant\Application\User\DTO\UpdateUserProfile;
use BarAssistant\Application\User\DTO\ChangeEmailRequest;
use BarAssistant\Application\User\DTO\AnonymizeUserRequest;
use BarAssistant\Application\User\DTO\ChangePasswordRequest;

class ProfileController extends Controller
{
    #[OAT\Get(path: '/profile', tags: ['Profile'], operationId: 'showProfile', description: 'Show current user profile information', summary: 'Show profile')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(ProfileResource::class),
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
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    public function update(UserService $userService, UpdateUserRequest $request): Response
    {
        $profileRequest = ProfileRequest::fromIlluminateRequest($request);

        $currentUser = $request->user();

        $userService->updateUserProfile(new UpdateUserProfile(
            userId: $currentUser->id,
            name: $profileRequest->name,
            language: $profileRequest->settings?->language,
            theme: $profileRequest->settings?->theme,
        ));

        $userService->changeEmail(new ChangeEmailRequest($currentUser->id, $profileRequest->email));

        return new Response(status: 204);
    }

    #[OAT\Post(path: '/profile/change-password', tags: ['Profile'], operationId: 'changePassword', description: 'Change user password', summary: 'Change password', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\ChangePasswordRequest::class),
        ]
    ))]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    public function changePassword(UserService $userService, UpdatePasswordRequest $request): Response
    {
        $changePasswordRequest = BAO\Schemas\ChangePasswordRequest::fromIlluminateRequest($request);
        $currentUser = $request->user();

        if (!Hash::check($changePasswordRequest->currentPassword, $currentUser->password)) {
            abort(403);
        }

        $userService->changePassword(new ChangePasswordRequest(
            userId: $currentUser->id,
            newPasswordHash: Hash::make($changePasswordRequest->password),
        ));

        $currentUser->tokens()->delete();

        return new Response(status: 204);
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

    #[OAT\Delete(path: '/profile', tags: ['Profile'], operationId: 'deleteProfile', description: 'Delete your profile and account', summary: 'Delete profile', parameters: [
        new BAO\Parameters\DatabaseIdParameter(),
    ])]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(UserService $userService, Request $request): Response
    {
        $user = $request->user();

        $userService->anonymizeUserAccount(new AnonymizeUserRequest($user->id));

        return new Response(null, 204);
    }
}
