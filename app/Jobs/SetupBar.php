<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Kami\Cocktail\External\BarOptionsEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\External\Import\FromDataPack;

class SetupBar implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $barId,
        private readonly int $userId,
        private ?BarOptionsEnum $barOptions = null,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(FromDataPack $import): void
    {
        $dataDisk = Storage::disk('data-files');

        $import->process($dataDisk, $this->barId, $this->userId, $this->barOptions);
    }
}
