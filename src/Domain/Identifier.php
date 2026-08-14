<?php

declare(strict_types=1);

namespace BarAssistant\Domain;

interface Identifier
{
    /**
     * Check if this identifier equals another
     */
    public function equals(self $other): bool;
}
