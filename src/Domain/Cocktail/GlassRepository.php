<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

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
}
