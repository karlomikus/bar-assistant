<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

/**
 * Represent hex color
 */
final readonly class Color
{
    private function __construct(private string $hex)
    {
    }

    public static function fromHexString(string $hex): self
    {
        // Validate hex color format (3 or 6 characters)
        if (!preg_match('/^#?[0-9A-Fa-f]{3}(?:[0-9A-Fa-f]{3})?$/', $hex)) {
            throw new \InvalidArgumentException('Invalid hex color format');
        }

        // Remove '#' if present for processing
        $hexValue = ltrim($hex, '#');

        // Expand short hex format (e.g., #333 to #333333)
        if (strlen($hexValue) === 3) {
            $hexValue = $hexValue[0] . $hexValue[0] . $hexValue[1] . $hexValue[1] . $hexValue[2] . $hexValue[2];
        }

        // Ensure the hex color starts with '#'
        $hex = '#' . $hexValue;

        return new self($hex);
    }

    public function toHexString(): string
    {
        return $this->hex;
    }
}
