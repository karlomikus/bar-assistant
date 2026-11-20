<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Support\Facades\Storage;
use Kami\Cocktail\External\BarOptionsEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Kami\Cocktail\External\Import\FromDataPack;
use Illuminate\Queue\Attributes\WithoutRelations;
use Kami\Cocktail\External\Import\DataPackMediaMode;
use Kami\Cocktail\External\Import\ExistingResourcePolicy;

class SyncBarRecipes implements ShouldQueue
{
    use \Illuminate\Foundation\Queue\Queueable;

    public function __construct(
        #[WithoutRelations]
        private readonly Bar $bar,
        #[WithoutRelations]
        private readonly User $user,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(FromDataPack $import): void
    {
        $dataDisk = Storage::disk('data-files');

        $import->process(
            dataDisk: $dataDisk,
            barId: $this->bar->id,
            userId: $this->user->id,
            flag: BarOptionsEnum::Cocktails,
            mediaMode: DataPackMediaMode::StarterCatalog,
            existingResourcePolicy: ExistingResourcePolicy::FillMissingScalars,
        );
    }
}
