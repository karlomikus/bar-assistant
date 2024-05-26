<?php

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Kami\Cocktail\Models\Export;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Kami\Cocktail\External\Export\Recipes;
use Kami\Cocktail\External\ExportTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;

class StartRecipesExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $barId,
        private readonly string $type,
        #[WithoutRelations]
        private readonly Export $export,
    ) {
    }

    public function handle(Recipes $exporter): void
    {
        $type = ExportTypeEnum::tryFrom($this->type) ?? ExportTypeEnum::JSON;

        $exporter->process($this->barId, $this->export->getFullPath(), $type);

        $this->export->markAsDone();
    }
}
