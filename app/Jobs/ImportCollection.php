<?php

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\External\Import\FromCollection;
use Kami\Cocktail\External\Import\DuplicateActionsEnum;

class ImportCollection implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly array $source, private readonly int $userId, private readonly int $barId, private readonly DuplicateActionsEnum $duplicateActions)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(FromCollection $collectionImporter): void
    {
        $collectionImporter->process($this->source, $this->userId, $this->barId, $this->duplicateActions);
    }
}
