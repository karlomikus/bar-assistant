<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Http\Resources\UserResource;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return new UserResource($user);
    }
}
