<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\DataPack;

use JsonSerializable;
use Kami\Cocktail\Models\Image as ImageModel;

readonly class Image implements JsonSerializable
{
    private function __construct(
        public string $uri,
        public string $copyright,
        public int $sort = 0,
        public ?string $placeholderHash = null,
    ) {
    }

    public static function fromModel(ImageModel $model): self
    {
        return new self(
            $model->getImageExteralURI(),
            $model->copyright ?? '',
            $model->sort,
            $model->placeholder_hash,
        );
    }

    public static function fromArray(array $sourceArray): self
    {
        return new self(
            $sourceArray['uri'] ?? '',
            $sourceArray['copyright'] ?? '',
            $sourceArray['sort'] ?? 0,
            $sourceArray['placeholder_hash'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'sort' => $this->sort,
            'placeholder_hash' => $this->placeholderHash,
            'copyright' => $this->copyright,
        ];
    }

    public function getLocalFilePath(): string
    {
        $parts = parse_url($this->uri);

        if ($parts['scheme'] === 'file') {
            return $parts['path'] ?? '';
        }

        return $this->uri;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
