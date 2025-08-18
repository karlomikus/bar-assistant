<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Kami\Cocktail\External\BarOptionsEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\External\Import\FromDataPack;
use Illuminate\Queue\Attributes\WithoutRelations;

class SyncBarRecipes implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

        $import->process($dataDisk, $this->bar, $this->user, BarOptionsEnum::Cocktails);
    }
}
