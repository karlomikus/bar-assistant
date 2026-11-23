<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
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
use BarAssistant\Application\Exception\EntityNotFoundException;

class ProfileController extends Controller
{
    #[OAT\Get(path: '/profile', tags: ['Profile'], operationId: 'showProfile', description: 'Show current user profile information', summary: 'Show profile')]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(ProfileResource::class),
    ])]
    #[BAO\NotAuthorizedResponse]
    public function show(Request $request): JsonResource
    {
        $user = $this->authenticatedUser($request);
        $user->load([
            'memberships' => fn ($memberships) => $memberships->chaperone(),
            'oauthCredentials',
        ]);

        return new ProfileResource($user);
    }

    #[OAT\Post(path: '/profile', tags: ['Profile'], operationId: 'updateProfile', description: 'Update user profile', summary: 'Update profile', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\ProfileRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(response: 204)]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function update(UserService $userService, UpdateUserRequest $request): Response
    {
        $profileRequest = ProfileRequest::fromIlluminateRequest($request);
        $currentUser = $this->authenticatedUser($request);

        try {
            $userService->updateUserProfile(new UpdateUserProfile(
                userId: $currentUser->id,
                name: $profileRequest->name,
                language: $profileRequest->settings?->language,
                theme: $profileRequest->settings?->theme,
            ));

            $userService->changeEmail(new ChangeEmailRequest($currentUser->id, $profileRequest->email));
        } catch (EntityNotFoundException) {
            abort(404);
        }

        return new Response(status: 204);
    }

    #[OAT\Post(path: '/profile/change-password', tags: ['Profile'], operationId: 'changePassword', description: 'Change user password', summary: 'Change password', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\ChangePasswordRequest::class),
        ]
    ))]
    #[BAO\SuccessfulResponse(response: 204)]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    public function changePassword(UserService $userService, UpdatePasswordRequest $request): Response
    {
        $changePasswordRequest = BAO\Schemas\ChangePasswordRequest::fromIlluminateRequest($request);
        $currentUser = $this->authenticatedUser($request);

        if (!Hash::check($changePasswordRequest->currentPassword, $currentUser->password)) {
            abort(403);
        }

        try {
            $userService->changePassword(new ChangePasswordRequest(
                userId: $currentUser->id,
                newPasswordHash: Hash::make($changePasswordRequest->password),
            ));
        } catch (EntityNotFoundException) {
            abort(404);
        }

        $currentUser->tokens()->delete();

        return new Response(status: 204);
    }

    #[OAT\Delete(path: '/profile/sso/{provider}', tags: ['Profile'], operationId: 'deleteSSO', description: 'Delete user\'s SSO provider', summary: 'Delete SSO provider', parameters: [
        new OAT\Parameter(name: 'provider', in: 'path', required: true, description: 'Provider ID', schema: new OAT\Schema(ref: OauthProvider::class)),
    ])]
    #[BAO\SuccessfulResponse(response: 204)]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function deleteSSOProvider(Request $request, string $provider): Response
    {
        $validProvider = OauthProvider::tryFrom($provider);
        if ($validProvider === null) {
            abort(404, 'Unsupported provider');
        }

        $this->authenticatedUser($request)->oauthCredentials()->where('provider', $validProvider->value)->delete();

        return new Response(null, 204);
    }

    #[OAT\Delete(path: '/profile', tags: ['Profile'], operationId: 'deleteProfile', description: 'Delete your profile and account', summary: 'Delete profile')]
    #[BAO\SuccessfulResponse(response: 204)]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function delete(UserService $userService, Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        try {
            $userService->anonymizeUserAccount(new AnonymizeUserRequest($user->id));
        } catch (EntityNotFoundException) {
            abort(404);
        }

        return new Response(null, 204);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        if (! ($user instanceof User)) {
            abort(401);
        }

        return $user;
    }
}
