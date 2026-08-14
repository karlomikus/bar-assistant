<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use Illuminate\Support\Facades\DB;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CollectionId;
use BarAssistant\Domain\Common\RecordTimestamps;
use Kami\Cocktail\Models\Cocktail as ModelCocktail;
use BarAssistant\Domain\Cocktail\CocktailCollection;
use Kami\Cocktail\Models\Collection as ModelCollection;
use BarAssistant\Domain\Cocktail\CocktailCollectionRepository;

final class EloquentCocktailCollectionRepository implements CocktailCollectionRepository
{
    public function findById(CollectionId $id): ?CocktailCollection
    {
        $model = ModelCollection::with('barMembership', 'cocktails')->find($id->value);

        if ($model === null) {
            return null;
        }

        return self::map($model);
    }

    public function save(CocktailCollection $collection): CocktailCollection
    {
        $model = DB::transaction(function () use ($collection): ModelCollection {
            $model = ModelCollection::findOrNew($collection->getId()?->value);
            $model->bar_membership_id = $collection->getMemberId()->value;
            $model->name = $collection->getName()->toString();
            $model->description = $collection->getDescription();
            $model->is_bar_shared = $collection->isBarShared();
            $model->created_at = $collection->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s');

            if ($collection->getRecordTimestamps()->wasUpdated()) {
                $model->updated_at = $collection->getRecordTimestamps()->getUpdatedAt()?->format('Y-m-d H:i:s');
            }

            $model->save();

            if ($collection->isTransient()) {
                $collection->setId(new CollectionId($model->id));
            }

            $cocktailIds = array_map(
                static fn (CocktailId $cocktailId): int => $cocktailId->value,
                $collection->getCocktailIds(),
            );

            $allowedCocktailIds = [];
            if (!empty($cocktailIds)) {
                $allowedCocktailIds = ModelCocktail::query()
                    ->where('bar_id', $collection->getBarId()->value)
                    ->whereIn('id', $cocktailIds)
                    ->pluck('id')
                    ->all();
            }

            $model->cocktails()->sync($allowedCocktailIds);

            return $model->load('barMembership', 'cocktails');
        });

        return self::map($model);
    }

    public function delete(CollectionId $id): void
    {
        ModelCollection::destroy($id->value);
    }

    private static function map(ModelCollection $model): CocktailCollection
    {
        $barMembership = $model->barMembership;

        $collection = CocktailCollection::create(
            barId: new BarId($barMembership->bar_id),
            memberId: new MemberId($model->bar_membership_id),
            name: Name::fromString($model->name),
            recordTimestamps: RecordTimestamps::createdAt($model->created_at->toDateTimeImmutable())->updatedAt($model->updated_at?->toDateTimeImmutable()),
            description: $model->description,
            isBarShared: $model->is_bar_shared,
            cocktailIds: array_map(
                static fn (ModelCocktail $cocktail): CocktailId => new CocktailId($cocktail->id),
                $model->cocktails->all(),
            ),
        );

        $collection->setId(new CollectionId($model->id));

        return $collection;
    }
}
