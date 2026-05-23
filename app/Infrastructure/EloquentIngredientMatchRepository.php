<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use Kami\Cocktail\Models\Ingredient as Model;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Ingredient\IngredientMatch;
use BarAssistant\Domain\Ingredient\IngredientMatchRepository;

final class EloquentIngredientMatchRepository implements IngredientMatchRepository
{
    /**
     * @return IngredientMatch[]
     */
    public function findManyByBarId(BarId $barId): array
    {
        $models = Model::select('id', 'name')
            ->where('bar_id', $barId->value)
            ->get();

        $matches = [];
        foreach ($models as $model) {
            $matches[] = IngredientMatch::create(
                ingredientId: new IngredientId($model->id),
                name: Name::fromString($model->name),
            );
        }

        return $matches;
    }
}
