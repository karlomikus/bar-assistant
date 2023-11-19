<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\PasswordReset;
use Illuminate\Support\Facades\Password;
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\RegisterRequest;
use Kami\Cocktail\Http\Resources\ProfileResource;

class AuthController extends Controller
{
    public function authenticate(Request $request): JsonResource
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $token = $request->user()->createToken('web_app_login');

            return new TokenResource($token);
        }

        abort(404, 'Unable to authenticate. Check your login credentials and try again.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(status: 204);
    }

    public function register(RegisterRequest $req): JsonResource
    {
        if (config('bar-assistant.allow_registration') === false) {
            abort(404, 'Registrations are closed.');
        }

        $user = new User();
        $user->name = $req->post('name');
        $user->password = Hash::make($req->post('password'));
        $user->email = $req->post('email');
        $user->email_verified_at = now();
        $user->save();

        return new ProfileResource($user);
    }

    public function passwordForgot(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'), function (User $user, string $token) {
            Mail::to($user)->queue(new PasswordReset($token));

            return Password::RESET_LINK_SENT;
        });

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(status: 204);
        }

        return response()->json([
            'status' => $status
        ], 400);
    }

    public function passwordReset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:5|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(status: 204);
        }

        return response()->json([
            'status' => $status
        ], 400);
    }
}
