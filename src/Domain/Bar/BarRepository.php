<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

interface BarRepository
{
    public function findById(BarId $id): ?Bar;
}
