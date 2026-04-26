<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI\DTO;

final readonly class ImagePromptConfiguration
{
    public function __construct(
        public string $prompt,
    ) {
    }
}
