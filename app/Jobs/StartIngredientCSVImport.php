<?php

declare(strict_types=1);

namespace Kami\Cocktail\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\External\Import\FromIngredientCSV;

class StartIngredientCSVImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $barId,
        private readonly int $userId,
        private readonly string $filepath,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importer = new FromIngredientCSV($this->barId, $this->userId);

        try {
            $importer->process(Storage::disk('temp-uploads')->path($this->filepath));
        } catch (Throwable $e) {
            Log::error('Error importing ingredients from CSV: ' . $e->getMessage());
        }

        Storage::disk('temp-uploads')->delete($this->filepath);
    }
}
