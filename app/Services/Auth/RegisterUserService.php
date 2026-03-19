<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use Psr\Log\LoggerInterface;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\ConfirmAccount;
use BarAssistant\Application\User\UserService;
use Kami\Cocktail\OpenAPI\Schemas\RegisterRequest;
use BarAssistant\Application\User\DTO\RegisterUserRequest;

final readonly class RegisterUserService
{
    public function __construct(private UserService $userService, private LoggerInterface $log)
    {
    }

    public function register(RegisterRequest $registerRequest): User
    {
        $registrationsEnabled = config('bar-assistant.allow_registration');
        $requireConfirmation = config('bar-assistant.mail_require_confirmation');

        if ($registrationsEnabled === false) {
            throw new \Exception('Registrations are disabled');
        }

        $userResult = $this->userService->register(new RegisterUserRequest(
            name: $registerRequest->name,
            email: $registerRequest->email,
            passwordHash: $registerRequest->hashedPassword,
            confirmAccount: $requireConfirmation === false,
        ));

        $userModel = User::find($userResult->id);

        if ($requireConfirmation === true) {
            Mail::to($userModel)->queue(new ConfirmAccount($userModel->id, sha1($userModel->email)));
            $this->log->info('Confirmation email sent', [
                'email' => $userModel->email,
            ]);
        }

        $this->log->info('User registered', [
            'email' => $userModel->email,
        ]);

        return $userModel;
    }
}
