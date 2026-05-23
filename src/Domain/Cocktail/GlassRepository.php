<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Bar\BarId;

interface GlassRepository
{
    /**
     * Find a glass by its ID
     */
    public function findById(GlassId $id): ?Glass;

    /**
     * Save a glass (insert or update)
     */
    public function save(Glass $glass): Glass;

    /**
     * Delete a glass by its ID
     */
    public function delete(GlassId $id): void;

    /**
     * @return Glass[]
     */
    public function findAllInBar(BarId $barId): array;
}
