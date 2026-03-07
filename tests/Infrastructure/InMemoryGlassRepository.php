<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Cocktail\Glass;
use BarAssistant\Domain\Cocktail\GlassId;
use BarAssistant\Domain\Cocktail\GlassRepository;

final class InMemoryGlassRepository implements GlassRepository
{
    private int $nextId = 1;

    /**
     * @param array<int, Glass> $items
     */
    public function __construct(private array $items = [])
    {
    }

    public function findById(GlassId $id): ?Glass
    {
        return $this->items[$id->value] ?? null;
    }

    public function save(Glass $glass): Glass
    {
        if ($glass->isTransient()) {
            $glass->setId(new GlassId($this->nextId++));
        }

        /** @var GlassId $id */
        $id = $glass->getId();
        $this->items[$id->value] = $glass;

        return $glass;
    }

    public function delete(GlassId $id): void
    {
        unset($this->items[$id->value]);
    }
}
