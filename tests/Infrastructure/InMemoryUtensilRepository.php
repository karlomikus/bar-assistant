<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Cocktail\Utensil;
use BarAssistant\Domain\Cocktail\UtensilId;
use BarAssistant\Domain\Cocktail\UtensilRepository;

final class InMemoryUtensilRepository implements UtensilRepository
{
    private int $nextId = 1;

    /**
     * @param array<int, Utensil> $items
     */
    public function __construct(private array $items = [])
    {
    }

    public function findById(UtensilId $id): ?Utensil
    {
        return $this->items[$id->value] ?? null;
    }

    public function save(Utensil $utensil): Utensil
    {
        if ($utensil->isTransient()) {
            $utensil->setId(new UtensilId($this->nextId++));
        }

        /** @var UtensilId $id */
        $id = $utensil->getId();
        $this->items[$id->value] = $utensil;

        return $utensil;
    }

    public function delete(UtensilId $id): void
    {
        unset($this->items[$id->value]);
    }
}
