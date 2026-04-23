<?php

declare(strict_types=1);

namespace Kami\Cocktail\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use Kami\Cocktail\Models\Cocktail as Model;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CocktailMatch;
use BarAssistant\Domain\Cocktail\CocktailMatchRepository;

final class EloquentCocktailMatchRepository implements CocktailMatchRepository
{
    /**
     * @return CocktailMatch[]
     */
    public function findManyByBarId(BarId $barId): array
    {
        $models = Model::select('id', 'name')
            ->where('bar_id', $barId->value)
            ->get();

        $matches = [];
        foreach ($models as $model) {
            $matches[] = CocktailMatch::create(
                cocktailId: new CocktailId($model->id),
                name: Name::fromString($model->name),
            );
        }

        return $matches;
    }
}
