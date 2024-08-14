<?php

namespace Kami\Cocktail\Jobs;

use Illuminate\Bus\Queueable;
use Kami\Cocktail\Models\Export;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Kami\Cocktail\External\Export\ToDataPack;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Kami\Cocktail\External\Export\ToSchemaDraft2;
use Kami\Cocktail\External\ExportTypeEnum;

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
    ) {
    }

    public function handle(): void
    {
        if ($this->type === ExportTypeEnum::Datapack) {
            resolve(ToDataPack::class)->process($this->barId, $this->export->filename);
        }

        if ($this->type === ExportTypeEnum::Schema) {
            resolve(ToSchemaDraft2::class)->process($this->barId, $this->export->filename);
        }


        $this->export->markAsDone();
    }
}
