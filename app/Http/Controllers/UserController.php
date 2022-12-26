<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Http\Resources\ProfileResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\UpdateUserRequest;

// TODO: Rename to profile or smthing
class UserController extends Controller
{
    public function show(Request $request): JsonResource
    {
        return new ProfileResource(
            $request->user()->load('favorites', 'shelfIngredients', 'shoppingLists')
        );
    }

    public function update(UpdateUserRequest $request): JsonResource
    {
        $currentUser = $request->user();
        $currentUser->name = $request->post('name');
        $currentUser->email = $request->post('email');

        if ($request->has('password')) {
            $currentUser->password = Hash::make($request->post('password'));

            $currentUser->tokens()->delete();
        }

        $currentUser->save();

        return new ProfileResource(
            $request->user()->load('favorites', 'shelfIngredients', 'shoppingLists')
        );
    }
}
