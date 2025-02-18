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
class HasManyDescendants extends HasMany
{
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->where($this->getForeignKeyName(), 'like', function ($subQuery) {
                $subQuery->selectRaw("id || '/%'")->from('ingredients')->where('id', $this->getParentKey());
            });
        }
    }

    /** @inheritDoc */
    public function addEagerConstraints(array $models)
    {
        $ids = collect($models)->pluck($this->getLocalKeyName());
        $this->query->select('ingredients.id AS _root_id', 'descendant.*')
            ->join('ingredients AS descendant', DB::raw("('/' || descendant.materialized_path || '/')"), 'LIKE', DB::raw("'%/' || ingredients.id || '/%'"))
            ->whereIn('ingredients.id', $ids)
            ->get();
    }

    /** @inheritDoc */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        foreach ($models as $model) {
            $descendants = $results->filter(function ($possibleDescendant) use ($model) {
                return $possibleDescendant['_root_id'] === $model->id;
            })->sort(function ($a, $b) use ($model) {
                // Sort by position in materialized path
                $path = $model->getMaterializedPath()->toArray();
                $posA = (int) array_search($a['id'], $path);
                $posB = (int) array_search($b['id'], $path);

                return $posA - $posB;
            });

            $model->setRelation($relation, $descendants);
        }

        return $models;
    }
}
