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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
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
    private tmpData $_tmpData;
    private bool $oidcAutoRegister = false;

    public function __construct()
    {
        $this->initOidcClient();
        $this->_tmpData = new tmpData();
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
        $auth_endpoint = $this->_oidcClient->getAuthUrl();
        $this->_tmpData->setClientCodeWithKey($this->_oidcClient->getHashedState(), $code);
        // Set these in session so we can use them later
        $this->_tmpData->setClientCodeTokenName($tokenName);
        $this->_tmpData->setClientCodeUrl($clientRedirecturl);
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
        if (!$this->_oidcClient->authenticate()) {
            abort(400, 'Unable to authenticate with OIDC.');
        }
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
        // be careful, the state in the oidc was cleared during the authenticate
        $hashedState = $this->_oidcClient->hash($_REQUEST['state']);
        if (!$this->_tmpData->updateByKey($hashedState)) {
            abort(400, 'Invalid state.');
        }
        $this->_tmpData->unsetClientCodeWithKey($hashedState);
        $tokenName = $this->_tmpData->getClientCodeTokenName();
        $clientRedirecturl = $this->_tmpData->getClientCodeUrl();
        $token = $user->createToken($tokenName, expiresAt: now()->addDays(14));
        $this->_tmpData->setClientCodeToken($token->plainTextToken);
        if (empty($clientRedirecturl)) {
            return response()->json("OIDC Success, please redirect your application", 200);
        }
        return redirect($clientRedirecturl);
    }

    public function tokenRequest(Request $request): JsonResource
    {
        $code = $request->input('code');
        if (!$code || !$this->_tmpData->checkClientCodeAndUpdate($code)) {
            abort(400, 'Invalid code.');
        }
        $token = $this->_tmpData->getClientCodeToken();
        $this->_tmpData->unsetAll();
        if (!$token) {
            abort(400, 'Token not found.');
        }
        return new TokenResource($token);
    }
}


class myOidc extends OpenIDConnectClient
{
    // for oidc client
    private string $_state = '';
    private string $_nonce = '';
    private string $_codeVerifier = '';
    private string $_hashedState = '';
    private string $hashMethod = 'sha256';

    // timeout for cache
    public int $cacheTimeout = 300;

    private function codeVerifierKey(): string
    {
        return $this->_hashedState . '.code_verifier';
    }

    private function nonceKey(): string
    {
        return $this->_hashedState . '.nonce';
    }


    public function getAuthUrl(): string
    {
        // I'd like to use OpenID-Connect-PHP directly,
        // but we can not get the redirect URL from the client, so I write this part
        $auth_endpoint = $this->getProviderConfigValue('authorization_endpoint');
        $response_type = 'code';

        // State essentially acts as a session key for OIDC
        $state = $this->setState($this->generateRandString());
        // The nonce is an arbitrary value
        $nonce = $this->setNonce($this->generateRandString());

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
        return $auth_endpoint;
    }

    public function authenticate(): bool
    {
        // If we have an authorization code, validate it,
        // becasue we store the state in the cache, let's try to get it from there
        if (isset($_REQUEST['code'])) {
            if (!$this->checkStateAndUpdate($_REQUEST['state'])) {
                return false;
            }
        }
        return parent::authenticate();
    }

    public function hash(string $data): string
    {
        return hash($this->hashMethod, $data);
    }

    public function checkState(string $state): bool
    {
        $hashedState = $this->hash($state);
        return Cache::has($hashedState);
    }


    public function checkStateAndUpdate(string $state): bool
    {
        if ($this->checkState($state)) {
            $this->_state = $state;
            $this->_hashedState = $this->hash($state);
            $this->_nonce = decrypt(Cache::get($this->nonceKey()));
            $this->_codeVerifier = decrypt(Cache::get($this->codeVerifierKey()));
            return true;
        }
        $this->_state = '';
        $this->_nonce = '';
        $this->_codeVerifier = '';
        return false;
    }

    protected function setState(string $state): string
    {
        $this->_state = $state;
        $this->_hashedState = $this->hash($state);
        Cache::put($this->_hashedState, true, $this->cacheTimeout);
        return $state;
    }

    protected function getState(): string
    {
        return $this->_state;
    }

    public function getHashedState(): string
    {
        return $this->_hashedState;
    }

    protected function unsetState(): void
    {
        Cache::forget($this->_hashedState);
        $this->_state = '';
        $this->_hashedState = '';
    }

    protected function setNonce(string $nonce): string
    {
        if (empty($this->_state)) {
            throw new \RuntimeException('State is not set.');
        }
        $encryptedNonce = encrypt($nonce);
        Cache::put($this->nonceKey(), $encryptedNonce, $this->cacheTimeout);
        return $nonce;
    }

    protected function getNonce(): string
    {
        return $this->_nonce;
    }

    protected function unsetNonce(): void
    {
        Cache::forget($this->nonceKey());
        $this->_nonce = '';
    }

    /**
     * Stores $codeVerifier
     */
    protected function setCodeVerifier(string $codeVerifier): string
    {
        if (empty($this->_state)) {
            throw new \RuntimeException('State is not set.');
        }
        $encryptedCodeVerifier = encrypt($codeVerifier);
        Cache::put($this->codeVerifierKey(), $encryptedCodeVerifier, $this->cacheTimeout);
        return $codeVerifier;
    }

    /**
     * Get stored codeVerifier
     *
     * @return string
     */
    protected function getCodeVerifier()
    {
        return $this->_codeVerifier;
    }

    /**
     * Cleanup codeVerifier
     *
     * @return void
     */
    protected function unsetCodeVerifier()
    {
        Cache::forget($this->codeVerifierKey());
        $this->_codeVerifier = '';
    }

}


class tmpData
{
    // for client of this server
    private string $_clientCodeToken = '';
    private string $_clientCodeTokenName = '';
    private string $_clientCodeUrl = '';
    private string $_hashedClientCode = '';
    private string $hashMethod = 'sha256';
    public int $cacheTimeout = 300;


    private function codeKeyWithPrefix(string $prefixKey): string
    {
        return $prefixKey . '.client_code';
    }

    private function tokenKey(): string
    {
        return $this->_hashedClientCode . '.token';
    }

    private function tokenNameKey(): string
    {
        return $this->_hashedClientCode . '.token_name';
    }

    private function urlKey(): string
    {
        return $this->_hashedClientCode . '.url';
    }


    public function hash(string $data): string
    {
        return hash($this->hashMethod, $data);
    }


    public function setClientCodeWithKey(string $key, string $code): string
    {
        $this->_hashedClientCode = $this->hash($code);
        $keyWithSuffix = $this->codeKeyWithPrefix($key);
        Cache::put($keyWithSuffix, encrypt($code), $this->cacheTimeout);
        Cache::put($this->_hashedClientCode, true, $this->cacheTimeout);
        return $code;
    }

    public function getClientCodeWithKey(string $key): string
    {
        $keyWithSuffix = $this->codeKeyWithPrefix($key);
        return decrypt(Cache::get($keyWithSuffix));
    }

    public function unsetClientCodeWithKey(string $key): void
    {
        $keyWithSuffix = $this->codeKeyWithPrefix($key);
        Cache::forget($keyWithSuffix);
    }


    public function updateByKey(string $key): bool
    {
        $code = $this->getClientCodeWithKey($key);
        return $this->checkClientCodeAndUpdate($code);
    }

    public function checkClientCode(string $code): bool
    {
        $hashedCode = $this->hash($code);
        return Cache::has($hashedCode);
    }

    public function checkClientCodeAndUpdate(string $code): bool
    {
        if ($this->checkClientCode($code)) {
            $this->_hashedClientCode = $this->hash($code);
            $this->_clientCodeToken = decrypt(Cache::get($this->tokenKey()));
            $this->_clientCodeTokenName = decrypt(Cache::get($this->tokenNameKey()));
            $this->_clientCodeUrl = decrypt(Cache::get($this->urlKey()));
            return true;
        }
        $this->_clientCodeToken = '';
        $this->_clientCodeTokenName = '';
        $this->_clientCodeUrl = '';
        return false;
    }


    public function setClientCodeToken(string $token): string
    {
        $encryptedToken = encrypt($token);
        Cache::put($this->tokenKey(), $encryptedToken, $this->cacheTimeout);
        return $token;
    }

    public function getClientCodeToken(): string
    {
        return $this->_clientCodeToken;
    }

    public function unsetClientCodeToken(): void
    {
        Cache::forget($this->tokenKey());
        $this->_clientCodeToken = '';
    }

    public function setClientCodeTokenName(string $tokenName): string
    {
        $encryptedTokenName = encrypt($tokenName);
        Cache::put($this->tokenNameKey(), $encryptedTokenName, $this->cacheTimeout);
        return $tokenName;
    }

    public function getClientCodeTokenName(): string
    {
        return $this->_clientCodeTokenName;
    }

    public function unsetClientCodeTokenName(): void
    {
        Cache::forget($this->tokenNameKey());
        $this->_clientCodeTokenName = '';
    }

    public function setClientCodeUrl(string $url): string
    {
        $encryptedUrl = encrypt($url);
        Cache::put($this->urlKey(), $encryptedUrl, $this->cacheTimeout);
        return $url;
    }

    public function getClientCodeUrl(): string
    {
        return $this->_clientCodeUrl;
    }

    public function unsetClientCodeUrl(): void
    {
        Cache::forget($this->urlKey());
        $this->_clientCodeUrl = '';
    }

    public function unsetAll(): void
    {
        Cache::forget($this->_hashedClientCode);
        $this->_hashedClientCode = '';
        $this->unsetClientCodeToken();
        $this->unsetClientCodeTokenName();
        $this->unsetClientCodeUrl();
    }

}



function decrypt(string|null $data): string
{
    if (empty($data)) {
        return '';
    }
    return Crypt::decrypt($data);
}

function encrypt(string|null $data): string
{
    if (empty($data)) {
        return '';
    }
    return Crypt::encrypt($data);
}
