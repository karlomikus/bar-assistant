<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Relations;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * @template TRelatedModel of \Kami\Cocktail\Models\Ingredient
 * @template TDeclaringModel of \Kami\Cocktail\Models\Ingredient
 *
 * @extends HasMany<TRelatedModel,TDeclaringModel>
 */
class HasManyAncestors extends HasMany
{
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->select('ancestor.*')
                ->join('ingredients AS ancestor', DB::raw("'/' || ingredients.materialized_path || '/'"), 'LIKE', DB::raw("'%/' || ancestor.id || '/%'"))
                ->where('ingredients.id', $this->getParentKey());
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $ids = collect($models)->pluck($this->getLocalKeyName());
        $this->query->select('ingredients.id as _leaf_id', 'ancestor.*')
            ->join('ingredients AS ancestor', DB::raw("instr('/' || ingredients.materialized_path || '/', '/' || ancestor.id || '/')"), '>', DB::raw('0'))
            ->whereIn('ingredients.id', $ids)
            ->whereNotNull('ingredients.materialized_path')
            ->get();
    }

    /** @inheritDoc */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        foreach ($models as $model) {
            $ancestors = $results->filter(function ($possibleAncestor) use ($model) {
                return $possibleAncestor['_leaf_id'] === $model->id;
            })->sort(function ($a, $b) use ($model) {
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
