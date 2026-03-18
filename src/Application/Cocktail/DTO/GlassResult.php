<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Cocktail\Glass;

final readonly class GlassResult
{
    /**
     * @param int[] $images
     */
    public function __construct(
        public int $id,
        public int $barId,
        public string $name,
        public ?string $description,
        public ?float $volume,
        public ?string $units,
        public array $images,
    ) {
    }

    public static function fromGlass(Glass $glass): self
    {
        return new self(
            id: $glass->getId()->value ?? 0,
            barId: $glass->getBarId()->value,
            name: $glass->getName()->toString(),
            description: $glass->getDescription(),
            volume: $glass->getVolume()?->amountMin,
            units: $glass->getVolume()?->units->value,
            images: array_map(
                static fn (ImageId $imageId) => $imageId->value,
                $glass->getImages()
            ),
        );
    }
}
