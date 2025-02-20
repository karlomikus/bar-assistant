<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Hash;
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
}
