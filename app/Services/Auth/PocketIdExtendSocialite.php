<?php

declare(strict_types=1);

namespace Kami\Cocktail\Services\Auth;

use Laravel\Socialite\Contracts\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class PocketIdExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('pocketid', Provider::class);
    }
}
