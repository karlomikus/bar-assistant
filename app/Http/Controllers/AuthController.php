<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Controllers;

use function bin2hex;

use Illuminate\Http\Request;
use Kami\Cocktail\Models\User;
use OpenApi\Attributes as OAT;
use Illuminate\Http\JsonResponse;
use Kami\Cocktail\OpenAPI as BAO;
use Jumbojett\OpenIDConnectClient;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Kami\Cocktail\Mail\PasswordReset;
use Kami\Cocktail\Mail\ConfirmAccount;
use Kami\Cocktail\Mail\PasswordChanged;
use Illuminate\Support\Facades\Password;
use Kami\Cocktail\Http\Resources\TokenResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Http\Requests\RegisterRequest;
use Kami\Cocktail\Http\Resources\ProfileResource;

// use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    private ?myOidc $_oidcClient = null;
    private bool $oidcAutoRegister = false;
    private int $oidcAuthTimeout = 300;

    public function __construct()
    {
        $this->initOidcClient();
    }

    #[OAT\Post(path: '/auth/login', tags: ['Authentication'], summary: 'Authenticate user and get a token', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\LoginRequest::class),
        ]
    ), security: [])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Token::class),
    ])]
    #[OAT\Response(response: 400, description: 'Unable to authenticate')]
    public function authenticate(Request $request): JsonResource
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            if (config('bar-assistant.mail_require_confirmation') === true) {
                abort(400, 'Unable to authenticate. Make sure you have confirmed your account and your login credentials are correct.');
            }

            abort(400, 'Unable to authenticate. Check your login credentials and try again.');
        }

        $tokenName = $request->input('token_name') ?? $request->userAgent() ?? 'Unknown device';
        $token = $user->createToken($tokenName, expiresAt: now()->addDays(14));

        return new TokenResource($token);
    }

    #[OAT\Post(path: '/auth/logout', tags: ['Authentication'], summary: 'Logout currently authenticated user')]
    #[OAT\Response(response: 204, description: 'Successful response')]
    public function logout(Request $request): JsonResponse
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken */
        $currentAccessToken = $request->user()->currentAccessToken();
        $currentAccessToken->delete();

        return response()->json(status: 204);
    }

    #[OAT\Post(path: '/auth/register', tags: ['Authentication'], summary: 'Register a new user', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(ref: BAO\Schemas\RegisterRequest::class),
        ]
    ), security: [])]
    #[OAT\Response(response: 200, description: 'Successful response', content: [
        new BAO\WrapObjectWithData(BAO\Schemas\Profile::class),
    ])]
    #[BAO\NotFoundResponse]
    public function register(RegisterRequest $req): JsonResource
    {
        if (config('bar-assistant.allow_registration') === false) {
            abort(404, 'Registrations are disabled.');
        }

        $requireConfirmation = config('bar-assistant.mail_require_confirmation');

        $user = new User();
        $user->name = $req->input('name');
        $user->password = Hash::make($req->input('password'));
        $user->email = $req->input('email');
        if ($requireConfirmation === false) {
            $user->email_verified_at = now();
        }
        $user->save();

        if ($requireConfirmation === true) {
            Mail::to($user)->queue(new ConfirmAccount($user->id, sha1($user->email)));
        }

        return new ProfileResource($user);
    }

    #[OAT\Post(path: '/auth/forgot-password', tags: ['Authentication'], summary: 'Request a new user password', requestBody: new OAT\RequestBody(
        required: true,
        content: [
            new OAT\JsonContent(type: 'object', properties: [
                new OAT\Property(type: 'string', property: 'email', example: 'admin@example.com'),
            ]),
        ]
    ), security: [])]
    #[OAT\Response(response: 204, description: 'Password reset link sent')]
    #[OAT\Response(response: 400, description: 'Unable to send password reset link')]
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

        abort(400);
    }

    #[OAT\Post(path: '/auth/reset-password', tags: ['Authentication'], summary: 'Reset user password', requestBody: new OAT\RequestBody(
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
    #[OAT\Response(response: 204, description: 'Password succssfully reset')]
    #[OAT\Response(response: 400, description: 'Unable to reset password')]
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
            Mail::to($request->post('email'))->queue(new PasswordChanged());

            return response()->json(status: 204);
        }

        abort(400, $status);
    }

    #[OAT\Get(path: '/auth/verify/{id}/{hash}', tags: ['Authentication'], summary: 'Confirm user account', parameters: [
        new OAT\Parameter(name: 'id', in: 'path', required: true, description: 'Database id of a user', schema: new OAT\Schema(type: 'integer')),
        new OAT\Parameter(name: 'hash', in: 'path', required: true, description: 'Hash string sent to user email', schema: new OAT\Schema(type: 'string')),
    ], security: [])]
    #[OAT\Response(response: 204, description: 'Account confirmed')]
    #[BAO\NotAuthorizedResponse]
    #[BAO\NotFoundResponse]
    public function confirmAccount(string $userId, string $hash): JsonResponse
    {
        if (config('bar-assistant.mail_require_confirmation') === false) {
            abort(404);
        }

        $user = User::findOrFail($userId);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return response()->json(status: 204);
    }

    public function passwordCheck(Request $request): JsonResponse
    {
        $status = Hash::check($request->input('password'), $request->user()->password);

        return response()->json(['data' => [
            'status' => $status,
        ]]);
    }

    private function initOidcClient(): void
    {
        // if already initialized or OIDC is disabled, return
        if ($this->_oidcClient || !config('bar-assistant.oidc_enabled')) {
            return;
        }
        $clientId = config('bar-assistant.oidc_client_id');
        $clientSecret = config('bar-assistant.oidc_client_secret');
        $issuer = config('bar-assistant.oidc_issuer');
        $redirectUri = config('bar-assistant.oidc_redirect_uri');
        $scopes = config('bar-assistant.oidc_scopes') ?: ['openid', 'profile', 'email'];
        $oidcAutoRegister = config('bar-assistant.oidc_auto_register');
        // validate configuration
        if (empty($clientId) || empty($clientSecret) || empty($issuer)) {
            throw new \RuntimeException('OIDC configuration is incomplete. Please check OIDC_CLIENT_ID, OIDC_CLIENT_SECRET, and OIDC_ISSUER.');
        }
        // set default redirect URI if not provided
        if (empty($redirectUri)) {
            $appUrl = config('app.url') ?: 'http://localhost'; // default http://localhost
            $redirectUri = rtrim($appUrl, '/') . '/api/auth/oidc/callback';
        }
        // create OIDC client
        $oidc = new myOidc($issuer, $clientId, $clientSecret);
        $oidc->setRedirectURL($redirectUri);
        $oidc->addScope($scopes);
        $this->oidcAutoRegister = $oidcAutoRegister;
        $this->_oidcClient = $oidc;
    }

    public function startOidc(Request $request): JsonResponse
    {
        if (!$this->_oidcClient) {
            abort(400, 'OIDC is not enabled.');
        }
        $clientRedirecturl = $request->input('redirect_url');
        $tokenName = $request->input('token_name') ?? $request->userAgent() ?? 'OIDC';
        // Generate a code to identify the request
        $code = bin2hex(random_bytes(16));
        // Set these in session so we can use them later
        $this->setClientCode($code);
        $this->setClientCodeTokenName($code, $tokenName);
        $this->setClientCodeUrl($code, $clientRedirecturl);
        $auth_endpoint = $this->_oidcClient->getAuthUrl();
        return response()->json([
            "data" => [
            'auth_url' => $auth_endpoint,
            'code' => $code,]
        ], 200);
    }

    public function oidcCallback(Request $request): RedirectResponse|JsonResponse
    {
        if (!$this->_oidcClient) {
            abort(400, 'OIDC is not enabled.');
        }
        $this->_oidcClient->authenticate();
        $userInfo = $this->_oidcClient->requestUserInfo();
        $email = $userInfo->email ?? '';
        if (empty($email)) {
            abort(400, 'Email not found in OIDC response.');
        }
        $requireConfirmation = config('bar-assistant.mail_require_confirmation');
        $user = User::where('email', $email)->first();
        if (!$user) {
            if (!$this->oidcAutoRegister) {
                abort(400, "Unable to authenticate with your email. Please register first.");
            } else {
                $user = new User();
                $user->name = $userInfo->name ?? $email;
                $user->email = $email;
                $user->password = Hash::make(bin2hex(random_bytes(16)));
                if ($requireConfirmation === false) {
                    $user->email_verified_at = now();
                }
                $user->save();
                if ($requireConfirmation === true) {
                    Mail::to($user)->queue(new ConfirmAccount($user->id, sha1($user->email)));
                    abort(400, "User with created. Please confirm your account first.");
                }

            }
        } elseif ($requireConfirmation === true && !$user->hasVerifiedEmail()) {
            abort(400, "User with email exists. Please confirm your account first.");
        }
        $code = $this->getClientCode();
        $tokenName = $this->getClientCodeTokenName($code);
        $token = $user->createToken($tokenName, expiresAt: now()->addDays(14));
        $clientRedirecturl = $this->getClientCodeUrl($code);
        $this->unsetClientCodeTokenName($code);
        $this->unsetClientCodeUrl($code);
        $this->setClientCodeToken($code, $token->plainTextToken);
        if (empty($clientRedirecturl)) {
            return response()->json("OIDC Success, please redirect your application", 200);
        }
        return redirect($clientRedirecturl);
    }

    public function tokenRequest(Request $request): JsonResource
    {
        $code = $request->input('code');
        if (!$code || $code !== $this->getClientCode()) {
            abort(400, 'Invalid code.');
        }
        $this->unsetClientCode();
        $token = $this->getClientCodeToken($code);
        $this->unsetClientCodeToken($code);
        if (!$token) {
            abort(400, 'Token not found.');
        }
        return new TokenResource($token);
    }

    protected function setClientCodeUrl(string $code, null|string $url): void
    {
        if (empty($url)) {
            return;
        }
        $this->setSessionKey($code."-url", $url, $this->oidcAuthTimeout);
    }

    protected function getClientCodeUrl(string $code): null|string
    {
        if (empty($code)) {
            return null;
        }
        return $this->getSessionKey($code."-url");
    }

    protected function unsetClientCodeUrl(string $code): void
    {
        $this->unsetSessionKey($code."-url");
    }

    protected function setClientCodeToken(string $code, string $token): void
    {
        $this->setSessionKey($code."-token", $token, $this->oidcAuthTimeout);
    }

    protected function getClientCodeToken(string $code): null|string
    {
        return $this->getSessionKey($code."-token");
    }

    protected function unsetClientCodeToken(string $code): void
    {
        $this->unsetSessionKey($code."-token");
    }

    protected function setClientCodeTokenName(string $code, string $tokenName): void
    {
        $this->setSessionKey($code."-token-name", $tokenName, $this->oidcAuthTimeout);
    }

    protected function getClientCodeTokenName(string $code): null|string
    {
        return $this->getSessionKey($code."-token-name");
    }

    protected function unsetClientCodeTokenName(string $code): void
    {
        $this->unsetSessionKey($code."-token-name");
    }

    protected function setClientCode(string $code): void
    {
        $this->setSessionKey('connect_code', $code, $this->oidcAuthTimeout);
    }

    protected function getClientCode(): null|string
    {
        return $this->getSessionKey('connect_code');
    }

    protected function unsetClientCode(): void
    {
        $this->unsetSessionKey('connect_code');
    }
    /**
     * Use session to manage a nonce
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    protected function setSessionKey(string $key, string $value, int $ttl = 0): void
    {
        $this->startSession();
        if ($ttl > 0) {
            $ttl_key = $key . '_ttl';
            $_SESSION[$ttl_key] = time() + $ttl;
        }
        $_SESSION[$key] = $value;
    }

    protected function unsetSessionKey(string $key): void
    {
        $this->startSession();
        unset($_SESSION[$key]);
    }

    protected function getSessionKey(string $key): null|string
    {
        $this->startSession();

        if (array_key_exists($key, $_SESSION)) {
            $ttl_key = $key . '_ttl';
            if (array_key_exists($ttl_key, $_SESSION) && $_SESSION[$ttl_key] < time()) {
                unset($_SESSION[$key]);
                unset($_SESSION[$ttl_key]);
                return null;
            }
            return $_SESSION[$key];
        }
        return null;
    }
}


class myOidc extends OpenIDConnectClient
{
    public function getAuthUrl(): string
    {
        // I'd like to use OpenID-Connect-PHP directly,
        // but we can not get the redirect URL from the client, so I write this part
        $auth_endpoint = $this->getProviderConfigValue('authorization_endpoint');
        $response_type = 'code';
        // The nonce is an arbitrary value
        $nonce = $this->setNonce($this->generateRandString());

        // State essentially acts as a session key for OIDC
        $state = $this->setState($this->generateRandString());

        // Generate the auth URL
        $auth_params = array_merge($this->getAuthParams(), [
            'response_type' => $response_type,
            'redirect_uri' => $this->getRedirectURL(),
            'client_id' => $this->getClientID(),
            'nonce' => $nonce,
            'state' => $state,
            'scope' => 'openid',
        ]);
        // If the client has been registered with additional scopes
        if (count($this->getScopes()) > 0) {
            $auth_params = array_merge($auth_params, ['scope' => implode(' ', array_merge($this->getScopes(), ['openid']))]);
        }
        // auth_endpoint should be a string
        // this is for phpstan.
        if (is_array($auth_endpoint)) {
            $auth_endpoint = implode('/', $auth_endpoint); //
        } elseif ($auth_endpoint === false) {
            $auth_endpoint = ''; //
        } elseif ($auth_endpoint === true) {
            $auth_endpoint = ''; //
        }
        // I just copied the code from OpenID-Connect-PHP
        $auth_endpoint .= (strpos($auth_endpoint, '?') === false ? '?' : '&') . http_build_query($auth_params, '', '&', $this->encType);
        $this->commitSession();
        return $auth_endpoint;
    }
}
