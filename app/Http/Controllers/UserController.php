<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Http\Resources\UserResource;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return new UserResource(
            $request->user()->load('favorites', 'shelfIngredients', 'shoppingLists')
        );
    }
}
