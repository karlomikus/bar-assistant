<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;

final class InMemoryCocktailMethodRepository implements CocktailMethodRepository
{
    private int $nextId = 1;

    /**
     * @param array<int, CocktailMethod> $items
     */
    public function __construct(private array $items = [])
    {
    }

    public function findAllInBar(BarId $barId): array
    {
        return array_values(array_filter(
            $this->items,
            fn (CocktailMethod $method) => $method->getBarId()->equals($barId)
        ));
    }

    public function findById(MethodId $id): ?CocktailMethod
    {
        return $this->items[$id->value] ?? null;
    }

    public function save(CocktailMethod $cocktailMethod): CocktailMethod
    {
        if ($cocktailMethod->isTransient()) {
            $cocktailMethod->setId(new MethodId($this->nextId++));
        }

        /** @var MethodId $id */
        $id = $cocktailMethod->getId();
        $this->items[$id->value] = $cocktailMethod;

        return $cocktailMethod;
    }

    public function delete(MethodId $id): void
    {
        unset($this->items[$id->value]);
    }
}
