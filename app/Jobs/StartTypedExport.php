<?php

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Kami\Cocktail\Models\Export;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Kami\Cocktail\External\ExportTypeEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kami\Cocktail\External\Export\ToDataPack;
use Kami\Cocktail\External\Export\ToRecipeType;
use Kami\Cocktail\External\ForceUnitConvertEnum;
use Illuminate\Queue\Attributes\WithoutRelations;

class StartTypedExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $barId,
        private readonly ExportTypeEnum $type,
        #[WithoutRelations]
        private readonly Export $export,
        private readonly ForceUnitConvertEnum $units,
    ) {
    }

    public function handle(): void
    {
        if ($this->type === ExportTypeEnum::Datapack) {
            resolve(ToDataPack::class)->process($this->barId, $this->export->filename);
        } else {
            resolve(ToRecipeType::class)->process($this->barId, $this->export->filename, $this->type, $this->units);
        }


        $this->export->markAsDone();
    }
}
