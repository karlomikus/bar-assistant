<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Search\SearchActionsAdapter;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Resources\ProfileResource;
use Kami\Cocktail\Http\Requests\UpdateUserRequest;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResource
    {
        return new ProfileResource(
            $request->user()->load('favorites', 'shelfIngredients', 'shoppingLists'),
            app(SearchActionsAdapter::class),
        );
    }

    public function update(UpdateUserRequest $request): JsonResource
    {
        $currentUser = $request->user();
        $currentUser->name = $request->post('name');
        $currentUser->email = $request->post('email');

        if ($request->has('password') && $request->post('password') !== null) {
            $currentUser->password = Hash::make($request->post('password'));

            $currentUser->tokens()->delete();
        }

        $currentUser->save();

        return new ProfileResource(
            $request->user()->load('favorites', 'shelfIngredients', 'shoppingLists'),
            app(SearchActionsAdapter::class),
        );
    }
}
