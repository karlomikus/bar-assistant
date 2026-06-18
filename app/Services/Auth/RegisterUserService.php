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
        $newAccountsRequireConfirmation = config('bar-assistant.mail_require_confirmation');

        if ($registrationsEnabled === false) {
            throw new \Exception('Registrations are disabled');
        }

        $userResult = $this->userService->register(new RegisterUserRequest(
            name: $registerRequest->name,
            email: $registerRequest->email,
            passwordHash: $registerRequest->hashedPassword,
            confirmAccount: $newAccountsRequireConfirmation === false,
        ));

        $userModel = User::find($userResult->id);

        if ($newAccountsRequireConfirmation === true) {
            // TODO: Move to laravel signed URLs, but confirmation is not a big deal for now
            Mail::to($userModel)->queue(new ConfirmAccount($userModel->id, sha1($userModel->email)));
            $this->log->info('Confirmation email sent', [
                'email' => $userModel->email,
            ]);
        }

        $this->log->info('User registered', [
            'email' => $userModel->email,
            'ip' => $registerRequest->ip,
        ]);

        return $userModel;
    }
}
