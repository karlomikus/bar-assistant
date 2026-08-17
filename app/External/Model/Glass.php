<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\Glass as GlassModel;
use Kami\Cocktail\Models\Image as ImageModel;

readonly class Glass implements SupportsDataPack
{
    /**
     * @param array<Image> $images
     */
    private function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?float $volume = null,
        public ?string $volumeUnits = null,
        public array $images = [],
    ) {
    }

    public static function fromModel(GlassModel $model, bool $useFileURI = true): self
    {
        $images = $model->images->map(fn (ImageModel $image) => Image::fromModel($image, $useFileURI))->toArray();

        return new self(
            id: $model->getExternalId(),
            name: $model->name,
            description: $model->description,
            volume: $model->volume,
            volumeUnits: $model->volume_units,
            images: $images,
        );
    }

    public static function fromDataPackArray(array $sourceArray): self
    {
        $images = [];
        foreach ($sourceArray['images'] ?? [] as $sourceImage) {
            $images[] = Image::fromDataPackArray($sourceImage);
        }

        return new self(
            id: $sourceArray['_id'] ?? $sourceArray['name'] ?? '',
            name: $sourceArray['name'] ?? '',
            description: $sourceArray['description'] ?? null,
            volume: $sourceArray['volume'] ?? null,
            volumeUnits: $sourceArray['volume_units'] ?? null,
            images: $images,
        );
    }

    public function toDataPackArray(): array
    {
        return [
            '_id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'volume' => $this->volume,
            'volume_units' => $this->volumeUnits,
            'images' => array_map(fn ($model) => $model->toDataPackArray(), $this->images),
        ];
    }
}
