<?php

declare(strict_types=1);

namespace Kami\Cocktail\Import;

use Kami\Cocktail\Models\BarMembership;
use Kami\Cocktail\Models\Collection as CocktailCollection;

class FromCollection
{
    public function __construct(private readonly FromArray $fromArrayImporter)
    {
    }

    public function process(array $sourceData, int $userId, int $barId): CocktailCollection
    {
        $barMembership = BarMembership::where('bar_id', $barId)->where('user_id', $userId)->firstOrFail();

        $collection = new CocktailCollection();
        $collection->name = $sourceData['name'];
        $collection->description = $sourceData['description'];
        $collection->bar_membership_id = $barMembership->id;
        $collection->save();

        foreach ($sourceData['cocktails'] as $cocktail) {
            $cocktail = $this->fromArrayImporter->process($cocktail, $userId, $barId);
            $cocktail->addToCollection($collection);
        }

        return $collection;
    }
}
