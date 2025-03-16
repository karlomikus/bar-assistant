<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Relations;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class HasManyDescendants extends BaseMaterializedPathRelation
{
    /** @inheritDoc */
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->where(function ($query) {
                $operatorValue = $this->parent->getMaterializedPath()->append($this->parent->id)->toStringPath();

                $query->where($this->getPathColumn(), 'like', $operatorValue . '%');
            });
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $rootIngredients)
    {
        $barId = $rootIngredients[0]->bar_id;
        $ids = collect($rootIngredients)->pluck('id');

        // Query all possible descendants of the given ingredients
        // Save the root ingredient of relation as _root_id so we can match it later
        $this->query->select('ingredients.id AS _root_id', 'descendant.*')
            ->join('ingredients AS descendant', DB::raw("('/' || descendant." . $this->getPathColumn() . " || '/')"), 'LIKE', DB::raw("'%/' || ingredients.id || '/%'"))
            ->whereIn('ingredients.id', $ids)
            ->where('ingredients.bar_id', $barId);
    }

    /** @inheritDoc */
    public function match(array $rootIngredients, EloquentCollection $descendants, $relation)
    {
        foreach ($rootIngredients as $rootIngredient) {
            $relationDescendants = $descendants->filter(function ($possibleDescendant) use ($rootIngredient) {
                return $possibleDescendant['_root_id'] === $rootIngredient->id;
            })->sort(function ($a, $b) use ($rootIngredient) {
                // Sort by position in materialized path
                $path = $rootIngredient->getMaterializedPath()->toArray();
                $posA = (int) array_search($a['id'], $path);
                $posB = (int) array_search($b['id'], $path);

                return $posA - $posB;
            });

            $rootIngredient->setRelation($relation, $relationDescendants);
        }

        return $rootIngredients;
    }
}
