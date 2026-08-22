<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Override;
use SocialiteProviders\OIDC\Provider;

class GenericOIDCProvider extends Provider
{
    protected bool $usesNonce = false;

    #[Override]
    public function redirect(): RedirectResponse
    {
        return new RedirectResponse($this->getAuthUrl(Str::random(40)));
    }
}
