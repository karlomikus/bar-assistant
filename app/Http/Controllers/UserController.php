<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    public function show(Request $request): JsonResource
    {
        return new UserResource(
            $request->user()->load('favorites', 'shelfIngredients', 'shoppingLists')
        );
    }
}
