<?php

declare(strict_types=1);

namespace Kami\Cocktail\GenAI;

use LogicException;
use InvalidArgumentException;

final class ImageStyleCatalog
{
    /** @var list<array{identifier: string, prompt: string}> */
    private const array DEFINITIONS = [
        [
            'identifier' => 'editorial',
            'prompt' => 'Premium editorial cocktail photography with realistic lighting and accurate liquid color. Bright soft natural side lighting creates gentle highlights on the glass and a subtle shadow on the table surface.',
        ],
        [
            'identifier' => 'vintage',
            'prompt' => 'Vintage copperplate engraving illustration style, pure black ink against a subtle warm ivory cotton paper background with faint, delicate paper grain, keeping high contrast for the black linework. Form, reflections, and glass facets are defined with crisp parallel lines, fine contour hatching, and open white highlights instead of solid gray shading. Includes intricate ornamental filigree and classic serif/script lettering integrated into the surface, reminiscent of 19th-century spirits labels and banknote engraving, clean vector-like ink precision.',
        ],
        [
            'identifier' => 'moody',
            'prompt' => 'Editorial cocktail photography, moody low-key studio lighting, directional shadows, high-angle close-up, shallow depth of field. Minimalist monochromatic set with textured woven linen surfaces, prefer general color balance of the main cocktail color. Thin-walled glassware, subtle condensation, fine film grain, intimate upscale bar aesthetic.',
        ],
        [
            'identifier' => 'illustration',
            'prompt' => 'Minimalist editorial vector illustration, clean geometric shapes with delicate thin hairline outlines, translucent overlapping gradient mesh and frosted glass light refraction effects, soft diffused color transitions, subtle light speckles and glowing highlights, delicate light gradient background, pale translucent washes with minimal texture, clean and uncluttered, modern Scandinavian graphic design aesthetic, flat yet luminous, no heavy shadows, no photorealism, no text.',
        ],
    ];

    /** @return list<ImageStyle> */
    public static function all(): array
    {
        $styles = [];
        $identifiers = [];

        foreach (self::DEFINITIONS as $definition) {
            $style = new ImageStyle($definition['identifier'], $definition['prompt']);

            if (isset($identifiers[$style->identifier])) {
                throw new LogicException("Duplicate image style identifier: {$style->identifier}");
            }

            $styles[] = $style;
            $identifiers[$style->identifier] = true;
        }

        return $styles;
    }

    /** @return list<string> */
    public static function identifiers(): array
    {
        return array_map(
            static fn (ImageStyle $style): string => $style->identifier,
            self::all(),
        );
    }

    public static function resolve(string $identifier): ?ImageStyle
    {
        foreach (self::all() as $style) {
            if ($style->identifier === $identifier) {
                return $style;
            }
        }

        return null;
    }

    public static function require(string $identifier): ImageStyle
    {
        return self::resolve($identifier)
            ?? throw new InvalidArgumentException("Unknown image style identifier: {$identifier}");
    }
}
