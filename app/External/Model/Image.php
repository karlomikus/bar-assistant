<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Model;

use Kami\Cocktail\External\SupportsDraft2;
use Kami\Cocktail\External\SupportsDataPack;
use Kami\Cocktail\Models\Image as ImageModel;

readonly class Image implements SupportsDraft2, SupportsDataPack
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

    public static function fromDataPackArray(array $sourceArray): self
    {
        return new self(
            $sourceArray['uri'] ?? '',
            $sourceArray['copyright'] ?? '',
            $sourceArray['sort'] ?? 0,
            $sourceArray['placeholder_hash'] ?? null,
        );
    }

    public function toDataPackArray(): array
    {
        return [
            'uri' => $this->uri,
            'sort' => $this->sort,
            'placeholder_hash' => $this->placeholderHash,
            'copyright' => $this->copyright,
        ];
    }

    public static function fromDraft2Array(array $sourceArray): self
    {
        return self::fromDataPackArray($sourceArray);
    }

    public function toDraft2Array(): array
    {
        return $this->toDataPackArray();
    }

    public function getLocalFilePath(): string
    {
        $parts = parse_url($this->uri);

        if ($parts['scheme'] === 'file') {
            return $parts['path'] ?? '';
        }

        return $this->uri;
    }
}
