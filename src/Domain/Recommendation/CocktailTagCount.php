<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Recommendation;

use BarAssistant\Domain\Common\Name;

final readonly class CocktailTagCount
{
    public function __construct(
        public Name $name,
        public int $count,
    ) {
    }
}
