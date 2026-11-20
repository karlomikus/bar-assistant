<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\OpenAPI as BAO;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Kami\Cocktail\Mail\PasswordReset;
use Kami\Cocktail\Mail\PasswordChanged;
use Illuminate\Support\Facades\Password;
use Kami\Cocktail\Http\Requests\LoginRequest;
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\RegisterRequest;
use Kami\Cocktail\Services\Auth\RegisterUserService;
use Kami\Cocktail\Http\Requests\ResetPasswordRequest;
use Kami\Cocktail\Http\Requests\ForgotPasswordRequest;
use Kami\Cocktail\OpenAPI\Schemas\RegisterRequest as SchemaRegisterRequest;

class AuthController extends Controller
{
    #[OAT\Post(path: '/auth/login', tags: ['Authentication'], operationId: 'authenticate', summary: 'Authenticate user', description: 'Authenticate user and get bearer token. Depending on server settings user might also require verified email.', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\LoginRequest::class),
        ]
    ), security: [])]
    #[BAO\SuccessfulResponse(content: [
        new BAO\WrapObjectWithData(TokenResource::class),
    ])]
    #[OAT\Response(response: 400, description: 'Unable to authenticate. Possible reasons: invalid credentials, unconfirmed account or disabled password login')]
    #[BAO\ValidationFailedResponse]
    #[BAO\RateLimitResponse]
    public function authenticate(LoginRequest $request): JsonResource
    {
        /** @var array{email: string, password: string, token_name?: string|null} $validated */
        $validated = $request->validated();
        $email = $validated['email'];
        $password = $validated['password'];

        if (!config('bar-assistant.enable_password_login')) {
            Log::warning('User tried to login with password, but password login is disabled', [
                'email' => $email,
            ]);
            abort(400, 'Password login is disabled.');
        }

        if (config('bar-assistant.mail_require_confirmation') === true) {
            $user = User::where('email', $email)->where('email_verified_at', '<>', null)->first();
        } else {
            $user = User::where('email', $email)->first();
        }

        if (!$user || !Hash::check($password, $user->password)) {
            Log::warning('User tried to login with invalid credentials', [
                'email' => $email,
                'is_verified' => $user?->hasVerifiedEmail(),
            ]);

            if (config('bar-assistant.mail_require_confirmation') === true) {
                abort(400, 'Unable to authenticate. Make sure you have confirmed your account and your login credentials are correct.');
            }

            abort(400, 'Unable to authenticate. Check your login credentials and try again.');
        }

        $tokenName = $validated['token_name'] ?? $request->userAgent() ?? 'Unknown device';
        /** @var int $days */
        $days = config('bar-assistant.auth.token_expiration_days');
        $expiresAt = $days > 0 ? now()->addDays($days) : null;
        $token = $user->createToken($tokenName, expiresAt: $expiresAt);

        return new TokenResource($token);
    }

    #[OAT\Post(path: '/auth/logout', tags: ['Authentication'], operationId: 'logout', description: 'Logout currently authenticated user. This will delete and disable bearer token.', summary: 'Logout')]
    #[OAT\Response(response: 204, description: 'Successful response')]
    #[OAT\Response(response: 401, description: 'Unauthenticated')]
    public function logout(Request $request): Response
    {
        $user = $request->user();
        if (!$user instanceof User) {
            abort(401);
        }

        /** @var \Laravel\Sanctum\PersonalAccessToken|null $currentAccessToken */
        $currentAccessToken = $user->currentAccessToken();
        if ($currentAccessToken) {
            $currentAccessToken->delete();
        }

        return response()->noContent();
    }

    #[OAT\Post(path: '/auth/register', tags: ['Authentication'], operationId: 'register', description: 'Register a new user account. If server has disabled registrations this will return 404 not found.', summary: 'Register', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\RegisterRequest::class),
        ]
    ), security: [])]
    #[BAO\SuccessfulResponse(response: 201, headers: [
        new OAT\Header(header: 'Location', description: 'URL of the new resource', schema: new OAT\Schema(type: 'string')),
    ])]
    #[BAO\NotFoundResponse]
    #[BAO\ValidationFailedResponse]
    #[BAO\RateLimitResponse]
    public function register(RegisterUserService $registerService, RegisterRequest $req): Response
    {
        if (config('bar-assistant.allow_registration') === false) {
            abort(404, 'Registrations are disabled.');
        }

        $registerService->register(
            SchemaRegisterRequest::fromIlluminateRequest($req)
        );

        return new Response(status: 201, headers: ['Location' => route('profile.show', [], false)]);
    }

    #[OAT\Post(path: '/auth/forgot-password', tags: ['Authentication'], operationId: 'passwordForgot', description: 'Send a new password reset link to users email address.', summary: 'Request password reset', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', required: ['email'], properties: [
                new OAT\Property(type: 'string', property: 'email', format: 'email', maxLength: 255, example: 'admin@example.com'),
            ]),
        ]
    ), security: [])]
    #[OAT\Response(response: 204, description: 'Password reset link sent')]
    #[BAO\ValidationFailedResponse]
    #[BAO\RateLimitResponse]
    public function passwordForgot(ForgotPasswordRequest $request): Response
    {
        /** @var array{email: string} $validated */
        $validated = $request->validated();

        $status = Password::sendResetLink(['email' => $validated['email']], function (User $user, string $token) {
            Mail::to($user)->queue(new PasswordReset($token));
            Log::info('Password reset link sent', [
                'email' => $user->email,
            ]);

            return Password::RESET_LINK_SENT;
        });

        if ($status === Password::RESET_LINK_SENT) {
            return response()->noContent();
        }

        return response()->noContent();
    }

    #[OAT\Post(path: '/auth/reset-password', tags: ['Authentication'], operationId: 'passwordReset', description: 'Reset user password with data from password reset request.', summary: 'Reset password', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', required: ['token', 'email', 'password', 'password_confirmation'], properties: [
                new OAT\Property(type: 'string', property: 'token', maxLength: 255, example: 'token-from-email'),
                new OAT\Property(type: 'string', property: 'email', format: 'email', maxLength: 255, example: 'admin@example.com'),
                new OAT\Property(type: 'string', property: 'password', format: 'password', example: 'password', minLength: 5, maxLength: 255),
                new OAT\Property(type: 'string', property: 'password_confirmation', format: 'password', example: 'password', minLength: 5, maxLength: 255),
            ]),
        ]
    ), security: [])]
    #[OAT\Response(response: 204, description: 'Password succesfully reset')]
    #[OAT\Response(response: 400, description: 'Unable to reset password')]
    #[BAO\ValidationFailedResponse]
    public function passwordReset(ResetPasswordRequest $request): Response
    {
        /** @var array{token: string, email: string, password: string, password_confirmation: string} $validated */
        $validated = $request->validated();

        $status = Password::reset(
            [
            'email' => $validated['email'],
            'password' => $validated['password'],
            'password_confirmation' => $validated['password_confirmation'],
            'token' => $validated['token'],
        ],
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Mail::to($validated['email'])->queue(new PasswordChanged());

            Log::info('Password reset successful', [
                'email' => $validated['email'],
            ]);

            return response()->noContent();
        }

        abort(400, is_string($status) ? $status : 'Unable to reset password');
    }

    #[OAT\Get(path: '/auth/verify/{id}/{hash}', tags: ['Authentication'], operationId: 'confirmAccount', description: 'Verify user email. This will return 404 if email verifications is disabled.', summary: 'Confirm account', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id of a user', schema: new OAT\Schema(type: 'integer')),
        new OAT\Parameter(name: 'hash', in: 'path', required: true, description: 'Hash string sent to user email', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[OAT\Response(response: 204, description: 'Account confirmed')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function confirmAccount(int $id, string $hash): Response
    {
        if (config('bar-assistant.mail_require_confirmation') === false) {
            abort(404);
        }

        $user = User::findOrFail($id);

        if (!hash_equals(sha1((string) $user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            Log::info('User account confirmed', [
                'email' => $user->email,
            ]);

            event(new Verified($user));
        }

        return response()->noContent();
    }
}
