<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\ConfirmAccount;
use Kami\Cocktail\OpenAPI\Schemas\RegisterRequest;

final class RegisterUserService
{
    public function register(RegisterRequest $newUserInfo): User
    {
        $requireConfirmation = config('bar-assistant.mail_require_confirmation');

        $user = new User();
        $user->name = $newUserInfo->name;
        $user->password = $newUserInfo->hashedPassword;
        $user->email = $newUserInfo->email;
        if ($requireConfirmation === false) {
            $user->email_verified_at = now();
        }
        $user->save();

        if ($requireConfirmation === true) {
            Mail::to($user)->queue(new ConfirmAccount($user->id, sha1($user->email)));
        }

        return $user;
    }
}
