<?php

declare(strict_types=1);

namespace Kami\Cocktail\ETL;

use JsonSerializable;
use Kami\Cocktail\Models\Image as ImageModel;

readonly class Image implements JsonSerializable
{
    public function __construct(
        public string|object|null $source,
        public int $sort = 0,
        public ?string $placeholderHash = null,
        public ?string $copyright = null,
    ) {
    }

    public static function fromModel(ImageModel $model): self
    {
        return new self(
            $model->getImageUrl(),
            $model->sort,
            $model->placeholder_hash,
            $model->copyright
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        $source = $sourceArray['source'] ?? $sourceArray['file_name'] ?? $sourceArray['url'] ?? null;

        return new self(
            $source,
            $sourceArray['sort'] ?? 0,
            $sourceArray['placeholder_hash'] ?? null,
            $sourceArray['copyright'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'sort' => $this->sort,
            'placeholder_hash' => $this->placeholderHash,
            'copyright' => $this->copyright,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
