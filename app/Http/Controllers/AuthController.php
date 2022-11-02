<?php
declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\User;
use Kami\Cocktail\SearchActions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Kami\Cocktail\Http\Resources\UserResource;
use Kami\Cocktail\Http\Requests\RegisterRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $token = $request->user()->createToken('web_app_login');

            return response()->json(['token' => $token->plainTextToken]);
        }

        return response()->json(['type' => 'api_error', 'message' => 'User authentication failed. Check your username and password and try again.'], 404);
    }

    public function logout()
    {
        Auth::logout();

        return response()->json([]);
    }

    public function register(RegisterRequest $req)
    {
        if (config('bar-assistant.allow_registration') == false) {
            throw new NotFoundHttpException();
        }

        $user = new User();
        $user->name = $req->string('name');
        $user->password = Hash::make($req->string('password'));
        $user->email = $req->string('email');
        $user->email_verified_at = now();
        $user->search_api_key = SearchActions::getPublicApiKey();
        $user->save();

        return new UserResource(
            $user->load('favorites', 'shelfIngredients', 'shoppingLists')
        );
    }
}
