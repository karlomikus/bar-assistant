<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Kami\Cocktail\External\Import\FromRecipesData;

class SetupBar implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        private readonly Bar $bar,
        #[WithoutRelations]
        private readonly User $user,
        private array $barOptions = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(FromRecipesData $import): void
    {
        $dataDisk = Storage::disk('data-files');

        $import->process($dataDisk, $this->bar, $this->user, $this->barOptions);
    }
}
