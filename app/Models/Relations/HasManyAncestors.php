<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Relations;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class HasManyAncestors extends BaseMaterializedPathRelation
{
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->whereIn('id', $this->parent->getMaterializedPath()->toArray());
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $barId = $models[0]->bar_id;
        $ids = collect($models)->pluck('id')->unique();

        // Query all possible ancestors of the given ingredients
        // Save the leaf ingredient of relation as _leaf_id so we can match it later
        $this->query->select('ingredients.id as _leaf_id', 'ancestor.*')
            ->join('ingredients AS ancestor', function ($join) use ($barId) {
                $join->on(DB::raw("instr('/' || ingredients." . $this->getPathColumn() . " || '/', '/' || ancestor.id || '/')"), '>', DB::raw('0'))
                    ->where('ancestor.bar_id', '=', $barId);
            })
            ->whereIn('ingredients.id', $ids)
            ->whereNotNull('ingredients.' . $this->getPathColumn())
            ->where('ingredients.bar_id', $barId);
    }

    /** @inheritDoc */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        foreach ($models as $model) {
            $ancestors = $results->filter(fn($possibleAncestor) => $possibleAncestor['_leaf_id'] === $model->id)->sort(function ($a, $b) use ($model) {
                // Sort by position in materialized path
                $path = $model->getMaterializedPath()->toArray();
                $posA = (int) array_search($a['id'], $path);
                $posB = (int) array_search($b['id'], $path);

                return $posA - $posB;
            });

            $model->setRelation($relation, $ancestors);
        }

        return $models;
    }
}
