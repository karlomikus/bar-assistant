<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ProfileResource;
use Kami\Cocktail\Http\Requests\UpdateUserRequest;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResource
    {
        return new ProfileResource($request->user());
    }

    public function update(UpdateUserRequest $request): JsonResource
    {
        $barId = $request->post('bar_id', null);
        $currentUser = $request->user();
        $currentUser->name = $request->post('name');
        $currentUser->email = $request->post('email');

        if ($request->has('password') && $request->post('password') !== null) {
            $currentUser->password = Hash::make($request->post('password'));

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
