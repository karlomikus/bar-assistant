<?php

declare(strict_types=1);

namespace Kami\Cocktail\Metrics;

use Illuminate\Support\Facades\DB;

class TotalActiveUsers extends BaseMetrics
{
    public function __invoke(): void
    {
        if (config('bar-assistant.mail_require_confirmation') === true) {
            $total = DB::table('users')->whereNotNull('email_verified_at')->count();
        } else {
            $total = DB::table('users')->count();
        }

        $metric = $this->registry->getOrRegisterGauge(
            $this->getDefaultNamespace(),
            'active_users_total',
            'Total number of users with confirmed email'
        );

        $metric->set($total);
    }
}
