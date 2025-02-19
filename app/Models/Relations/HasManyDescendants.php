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
                $query->where('materialized_path', 'like', $this->parent->materialized_path . $this->parent->id . '/%');
            });
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $rootIngredients)
    {
        $ids = collect($rootIngredients)->pluck('id');

        $this->query->select('ingredients.id AS _root_id', 'descendant.*')
            ->join('ingredients AS descendant', DB::raw("('/' || descendant.materialized_path || '/')"), 'LIKE', DB::raw("'%/' || ingredients.id || '/%'"))
            ->whereIn('ingredients.id', $ids)
            ->get();
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
