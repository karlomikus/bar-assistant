<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\Services\BarOptimizerService;

class StartBarOptimization implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $barId)
    {
    }

    public function handle(BarOptimizerService $optimizer): void
    {
        $optimizer->optimize($this->barId);
    }
}
