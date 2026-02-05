<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use BarAssistant\Domain\Image\Image;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\Image\ImageRepository;

/**
 * In-memory implementation of ImageRepository for testing purposes
 */
final class InMemoryImageRepository implements ImageRepository
{
    /**
     * @param array<int, Image> $images
     */
    public function __construct(private array $images = [])
    {
    }

    public function delete(ImageId $id): void
    {
        unset($this->images[$id->value]);
    }

    public function findById(ImageId $id): ?Image
    {
        return $this->images[$id->value] ?? null;
    }

    /**
     * @param ImageId[] $ids
     * @return Image[]
     */
    public function findMany(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (isset($this->images[$id->value])) {
                $result[] = $this->images[$id->value];
            }
        }

        return $result;
    }

    public function save(Image $image): Image
    {
        if ($image->isTransient()) {
            // Assign a new ID for transient images
            $nextId = empty($this->images) ? 1 : max(array_keys($this->images)) + 1;
            $image->setId(new ImageId($nextId));
        }

        $imageId = $image->getId();
        if ($imageId === null) {
            throw new \DomainException('Image ID must be set');
        }

        $this->images[$imageId->value] = $image;

        return $image;
    }
}
