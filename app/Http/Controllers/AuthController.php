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
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\RegisterRequest;
use Kami\Cocktail\Services\Auth\RegisterUserService;
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
    public function authenticate(Request $request): JsonResource
    {
        if (!config('bar-assistant.enable_password_login')) {
            Log::warning('User tried to login with password, but password login is disabled', [
                'email' => $request->email,
            ]);
            abort(400, 'Password login is disabled.');
        }

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (config('bar-assistant.mail_require_confirmation') === true) {
            $user = User::where('email', $request->email)->where('email_verified_at', '<>', null)->first();
        } else {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('User tried to login with invalid credentials', [
                'email' => $request->email,
                'is_verified' => $user?->hasVerifiedEmail(),
            ]);

            if (config('bar-assistant.mail_require_confirmation') === true) {
                abort(400, 'Unable to authenticate. Make sure you have confirmed your account and your login credentials are correct.');
            }

            abort(400, 'Unable to authenticate. Check your login credentials and try again.');
        }

        $tokenName = $request->input('token_name') ?? $request->userAgent() ?? 'Unknown device';
        $token = $user->createToken($tokenName, expiresAt: now()->addDays(14));

        return new TokenResource($token);
    }

    #[OAT\Post(path: '/auth/logout', tags: ['Authentication'], operationId: 'logout', description: 'Logout currently authenticated user. This will delete and disable bearer token.', summary: 'Logout')]
    #[OAT\Response(response: 204, description: 'Successful response')]
    public function logout(Request $request): Response
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken|null */
        $currentAccessToken = $request->user()->currentAccessToken();
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
    public function register(RegisterUserService $registerService, RegisterRequest $req): Response
    {
        if (config('bar-assistant.allow_registration') === false) {
            abort(404, 'Registrations are disabled.');
        }

        $user = $registerService->register(
            SchemaRegisterRequest::fromIlluminateRequest($req)
        );

        return new Response(status: 201, headers: ['Location' => route('profile.show', $user->id, false)]);
    }

    #[OAT\Post(path: '/auth/forgot-password', tags: ['Authentication'], operationId: 'passwordForgot', description: 'Send a new password reset link to users email address.', summary: 'Request password reset', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(type: 'string', property: 'email', example: 'admin@example.com'),
            ]),
        ]
    ), security: [])]
    #[OAT\Response(response: 204, description: 'Password reset link sent')]
    public function passwordForgot(Request $request): Response
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'), function (User $user, string $token) {
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
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(type: 'string', property: 'token', example: 'token-from-email'),
                new OAT\Property(type: 'string', property: 'email', example: 'admin@example.com'),
                new OAT\Property(type: 'string', property: 'password', example: 'password', minLength: 5),
                new OAT\Property(type: 'string', property: 'password_confirmation', example: 'password', minLength: 5),
            ]),
        ]
    ), security: [])]
    #[OAT\Response(response: 204, description: 'Password succesfully reset')]
    #[OAT\Response(response: 400, description: 'Unable to reset password')]
    public function passwordReset(Request $request): Response
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
            Mail::to($request->post('email'))->queue(new PasswordChanged());

            Log::info('Password reset successful', [
                'email' => $request->post('email'),
            ]);

            return response()->noContent();
        }

        abort(400, $status);
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
