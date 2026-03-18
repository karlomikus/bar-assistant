<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Cocktail\Utensil;
use Kami\Cocktail\Models\Utensil as Model;
use BarAssistant\Domain\Cocktail\UtensilId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\UtensilRepository;

final class EloquentUtensilRepository implements UtensilRepository
{
    public function findById(UtensilId $id): ?Utensil
    {
        $model = Model::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(Utensil $utensil): Utensil
    {
        $model = Model::findOrNew($utensil->getId()?->value);

        $model->bar_id = $utensil->getBarId()->value;
        $model->name = (string) $utensil->getName();
        $model->description = $utensil->getDescription();
        $model->created_at = $utensil->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        if ($utensil->getRecordTimestamps()->wasUpdated()) {
            $model->updated_at = $utensil->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        $model->save();

        return self::map($model);
    }

    public function delete(UtensilId $id): void
    {
        $model = Model::find($id->value);
        if ($model === null) {
            return;
        }

        $model->delete();
    }

    private static function map(Model $model): Utensil
    {
        return Utensil::create(
            barId: new BarId($model->bar_id),
            name: Name::fromString($model->name),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable())
                ->updatedAt($model->updated_at?->toDateTimeImmutable()),
            description: $model->description,
        )->setId(new UtensilId($model->id));
    }
}
