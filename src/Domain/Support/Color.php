<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Support;

/**
 * Represent hex color
 */
final readonly class Color
{
    private function __construct(public string $hex)
    {
    }

    public static function fromHexString(string $hex): self
    {
        // Validate hex color format
        if (!preg_match('/^#?[0-9A-Fa-f]{6}$/', $hex)) {
            throw new \InvalidArgumentException('Invalid hex color format');
        }

        // Ensure the hex color starts with '#'
        if ($hex[0] !== '#') {
            $hex = '#' . $hex;
        }

        return new self($hex);
    }
}
