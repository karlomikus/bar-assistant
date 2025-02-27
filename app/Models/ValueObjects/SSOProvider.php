<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Kami\Cocktail\Services\SSO\Providers;

readonly class SSOProvider
{
    public function __construct(public Providers $provider, public string $prettyName, public bool $isEnabled)
    {
    }
}
