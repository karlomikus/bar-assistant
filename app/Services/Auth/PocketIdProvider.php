<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class PocketIdProvider extends AbstractProvider
{
    public const IDENTIFIER = 'POCKETID';

    /**
     * @var array<string>
     */
    protected $scopes = ['openid profile email'];

    /**
     * @return array<string>
     */
    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->getConfig('base_url'), '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl() . '/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl() . '/api/oidc/token';
    }

    /**
     * @return array<mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl() . '/api/oidc/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @param array<mixed> $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
        ]);
    }
}
