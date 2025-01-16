<?php

declare(strict_types=1);

namespace Kami\Cocktail\OAuth;

use InvalidArgumentException;

class OAuthProvider
{
    public string $id;
    public string $clientId;
    public string $clientSecret;
    public string $type;
    public string $icon;
    public string $name;
    public string $userIdKey;
    public string $nameKey;
    public string $redirectUri;
    public string $authority;
    public string $authorizationEndpoint;
    public string $tokenEndpoint;
    public string $userInfoEndpoint;
    public string $scope;

    /**
     * Create an instance from an array
     *
     * @param array $data
     * @return self
     * @throws InvalidArgumentException if any required field is empty
     */
    public static function fromArray(array $data, string $redirectUri): self
    {
        $provider = new self();

        $provider->id = $data['id'] ?? '';
        $provider->clientId = $data['clientId'] ?? '';
        $provider->clientSecret = $data['clientSecret'] ?? '';
        $provider->type = $data['type'] ?? 'oidc';
        $provider->icon = $data['icon'] ?? 'oidc.svg';
        $provider->name = $data['name'] ?? '';
        $provider->userIdKey = $data['userIdKey'] ?? 'sub';
        $provider->nameKey = $data['nameKey'] ?? 'preferred_username';
        $provider->redirectUri = $redirectUri;
        $provider->authority = $data['authority'] ?? '';
        $provider->authorizationEndpoint = $data['authorizationEndpoint'] ?? '';
        $provider->tokenEndpoint = $data['tokenEndpoint'] ?? '';
        $provider->userInfoEndpoint = $data['userInfoEndpoint'] ?? '';
        $provider->scope = $data['scope'] ?? '';

        $provider->validate();

        return $provider;
    }

    /**
     * Validate that all required fields are non-empty.
     *
     * @throws InvalidArgumentException if any field is empty
     */
    private function validate(): void
    {
        foreach (get_object_vars($this) as $field => $value) {
            if (empty($value)) {
                throw new InvalidArgumentException("Field '{$field}' cannot be empty.");
            }
        }
    }
}
