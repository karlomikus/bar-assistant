<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Cocktail\Glass;
use Kami\Cocktail\Models\Glass as Model;
use BarAssistant\Domain\Cocktail\GlassId;
use Kami\Cocktail\Models\Image as ModelImage;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\GlassRepository;

final class EloquentGlassRepository implements GlassRepository
{
    public function findById(GlassId $id): ?Glass
    {
        $model = Model::with('images')->find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Glass $glass): Glass
    {
        $model = Model::with('images')->findOrNew($glass->getId()?->value);

        $model->bar_id = $glass->getBarId()->value;
        $model->name = (string) $glass->getName();
        $model->description = $glass->getDescription();
        $model->volume = $glass->getVolume()?->amountMin;
        $model->volume_units = $glass->getVolume()?->units->value;
        $model->created_at = $glass->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        if ($glass->getRecordTimestamps()->wasUpdated()) {
            $model->updated_at = $glass->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        $model->save();

        if (count($glass->getImages()) > 0) {
            $imageModels = ModelImage::findOrFail(array_map(
                fn (ImageId $imageId): int => $imageId->value,
                $glass->getImages()
            ));
            $model->attachImages($imageModels);
        }

        return self::map($model);
    }

    public function delete(GlassId $id): void
    {
        $model = Model::find($id->value);
        if ($model === null) {
            return;
        }

        $model->delete();
    }

    public function findAllInBar(BarId $barId): array
    {
        $models = Model::with('images')
            ->where('bar_id', $barId->value)
            ->get();

        $glasses = [];
        foreach ($models as $model) {
            $glasses[] = self::map($model);
        }

        return $glasses;
    }

    private static function map(Model $model): Glass
    {
        $glass = Glass::create(
            barId: new BarId($model->bar_id),
            name: Name::fromString($model->name),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable())
                ->updatedAt($model->updated_at?->toDateTimeImmutable()),
            description: $model->description,
            volume: $model->volume_units ? AmountWithUnits::from($model->volume ?? 0, Unit::from($model->volume_units)) : null,
        )->setId(new GlassId($model->id));

        foreach ($model->images as $imageModel) {
            $glass->addImage(new ImageId($imageModel->id));
        }

        return $glass;
    }
}
