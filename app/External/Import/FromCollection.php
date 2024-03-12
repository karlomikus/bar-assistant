<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Import;

use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Collection as CocktailCollection;
use Kami\Cocktail\External\Collection as CollectionExternal;

class FromCollection
{
    public function __construct(private readonly FromArray $fromArrayImporter)
    {
    }

    public function process(array $sourceData, int $userId, int $barId, DuplicateActionsEnum $duplicateAction): CocktailCollection
    {
        $barMembership = BarMembership::where('bar_id', $barId)->where('user_id', $userId)->firstOrFail();

        $externalCollection = CollectionExternal::fromArray($sourceData);
        $collection = new CocktailCollection();
        $collection->name = $externalCollection->name;
        $collection->description = $externalCollection->description;
        $collection->bar_membership_id = $barMembership->id;
        $collection->save();

        foreach ($externalCollection->cocktails as $cocktail) {
            $cocktail = $this->fromArrayImporter->process($cocktail->toArray(), $userId, $barId, $duplicateAction);
            $cocktail->addToCollection($collection);
        }

        return $collection;
    }
}
