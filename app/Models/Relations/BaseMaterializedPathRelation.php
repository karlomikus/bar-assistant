<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Relations;

use Kami\Cocktail\Models\Ingredient;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * @extends Relation<Ingredient, Ingredient, EloquentCollection<array-key, Ingredient>>
 */
abstract class BaseMaterializedPathRelation extends Relation
{
    private string $pathColumn;

    /**
     * @param Ingredient $parent In our case the root ingredient
     * @param string $pathColumn Name of column containing materialized path
     */
    public function __construct(Ingredient $parent, string $pathColumn)
    {
        $this->pathColumn = $pathColumn;

        parent::__construct(Ingredient::query(), $parent);
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $ingredient) {
            $ingredient->setRelation(
                $relation,
                $this->related->newCollection()
            );
        }

        return $models;
    }

    /** @inheritDoc */
    public function getResults()
    {
        return $this->query->get();
    }

    protected function getPathColumn(): string
    {
        return $this->pathColumn;
    }
}
