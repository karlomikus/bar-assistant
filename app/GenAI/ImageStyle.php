<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use InvalidArgumentException;

final readonly class ImageStyle
{
    public function __construct(
        public string $identifier,
        public string $prompt,
    ) {
        if (trim($identifier) === '') {
            throw new InvalidArgumentException('Image style identifier cannot be empty.');
        }

        if (trim($prompt) === '') {
            throw new InvalidArgumentException('Image style prompt cannot be empty.');
        }
    }
}
