<?php

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Kami\Cocktail\External\ExportTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\External\Export\ToDataPack;
use Kami\Cocktail\External\Export\ToRecipeType;
use Kami\Cocktail\External\ForceUnitConvertEnum;
use BarAssistant\Application\Export\ExportService;

class StartTypedExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $barId,
        private readonly ExportTypeEnum $type,
        private readonly int $exportId,
        private readonly string $filename,
        private readonly ForceUnitConvertEnum $units,
    ) {
    }

    public function handle(ExportService $exportService): void
    {
        if ($this->type === ExportTypeEnum::Datapack) {
            resolve(ToDataPack::class)->process($this->barId, $this->filename);
        } else {
            resolve(ToRecipeType::class)->process($this->barId, $this->filename, $this->type, $this->units);
        }

        $exportService->markAsDone($this->exportId);
    }
}
