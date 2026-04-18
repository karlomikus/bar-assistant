<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\DTO;

use Prism\Prism\Contracts\Schema;

final readonly class PromptConfiguration
{
    public function __construct(
        public string $prompt,
        public Schema $schema,
    ) {
    }
}
