<?php

declare(strict_types=1);

namespace Kami\Cocktail\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Laravel\Paddle\Events\SubscriptionCreated;

class ActivateBars
{
    public function handle(SubscriptionCreated $event): void
    {
        $subscription = $event->subscription;

        /** @var \Kami\Cocktail\Models\User */
        $user = $subscription->billable;

        Log::info('User "' . $user->email . '" created subscription.');

        foreach ($user->ownedBars as $bar) {
            Cache::forget('ba:bar:' . $bar->id);
        }

        $user->activateBars();
    }
}
