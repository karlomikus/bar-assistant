<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Export\Export;
use BarAssistant\Domain\Export\ExportId;
use Kami\Cocktail\Models\Export as ModelExport;
use BarAssistant\Domain\Export\ExportRepository;

final class EloquentExportRepository implements ExportRepository
{
    public function findById(ExportId $id): ?Export
    {
        $model = ModelExport::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Export $export): Export
    {
        $model = ModelExport::findOrNew($export->getId()?->value);
        $model->bar_id = $export->getBarId()->value;
        $model->created_user_id = $export->getCreatedUserId()->value;
        $model->filename = $export->getFilename();
        $model->is_done = $export->isDone();
        $model->created_at = $export->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');

        if ($export->getRecordTimestamps()->wasUpdated()) {
            $model->updated_at = $export->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }

        $model->save();

        if ($export->isTransient()) {
            $export->setId(new ExportId($model->id));
        }

        return $export;
    }

    public function delete(ExportId $id): void
    {
        $model = ModelExport::find($id->value);
        if ($model !== null) {
            $model->delete();
        }
    }

    private static function map(ModelExport $model): Export
    {
        $export = Export::create(
            barId: new BarId($model->bar_id),
            createdUserId: new UserId($model->created_user_id),
            filename: $model->filename,
        );

        if ($model->is_done) {
            $export->markAsDone();
        }

        $export->setId(new ExportId($model->id));

        return $export;
    }
}
