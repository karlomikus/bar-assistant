<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Common\RecordTimestamps;
use Kami\Cocktail\Models\CocktailMethod as Model;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;

final class EloquentCocktailMethodRepository implements CocktailMethodRepository
{
    public function findById(MethodId $id): ?CocktailMethod
    {
        $model = Model::find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(CocktailMethod $cocktailMethod): CocktailMethod
    {
        $model = Model::findOrNew($cocktailMethod->getId()?->value);

        $model->bar_id = $cocktailMethod->getBarId()->value;
        $model->name = (string) $cocktailMethod->getName();
        $model->dilution_percentage = (int) $cocktailMethod->getDilution()->toFloat();
        $model->description = $cocktailMethod->getDescription();
        $model->created_at = $cocktailMethod->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');
        if ($cocktailMethod->getRecordTimestamps()->wasUpdated()) {
            $model->updated_at = $cocktailMethod->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
        }
        $model->save();

        return self::map($model);
    }

    public function delete(MethodId $id): void
    {
        $model = Model::find($id->value);
        if ($model === null) {
            return;
        }

        $model->delete();
    }

    public function findAllInBar(BarId $barId): array
    {
        $models = Model::where('bar_id', $barId->value)->get();

        $methods = [];
        foreach ($models as $model) {
            $methods[] = self::map($model);
        }

        return $methods;
    }

    private static function map(Model $model): CocktailMethod
    {
        return CocktailMethod::create(
            barId: new BarId($model->bar_id),
            name: Name::fromString($model->name),
            dilution: Dilution::fromFloat((float) $model->dilution_percentage),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
            description: $model->description,
        )->setId(new MethodId($model->id));
    }
}
