<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Kami\Cocktail\Services\Auth\OauthProvider;

readonly class SSOProvider
{
    public function __construct(public OauthProvider $provider, public string $prettyName, public bool $isEnabled)
    {
    }
}
