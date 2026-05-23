<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common\Traits;

use BarAssistant\Domain\Image\ImageId;

trait HasImages
{
    /** @var ImageId[] */
    private array $images = [];

    /**
     * @return ImageId[]
     */
    final public function getImages(): array
    {
        return $this->images;
    }

    final public function addImage(ImageId $imageId): self
    {
        foreach ($this->images as $existingImageId) {
            if ($existingImageId->equals($imageId)) {
                return $this;
            }
        }

        $this->images[] = $imageId;

        return $this;
    }

    final public function removeImage(ImageId $imageId): self
    {
        $this->images = array_values(array_filter(
            $this->images,
            static fn (ImageId $existingImageId) => !$existingImageId->equals($imageId)
        ));

        return $this;
    }

    final public function removeAllImages(): self
    {
        $this->images = [];

        return $this;
    }

    /**
     * @param array<ImageId> $imageIds
     */
    final public function setImages(array $imageIds): self
    {
        $this->removeAllImages();
        foreach ($imageIds as $id) {
            $this->addImage($id);
        }
        return $this;
    }

    final public function hasImage(ImageId $imageId): bool
    {
        foreach ($this->images as $existingImageId) {
            if ($existingImageId->equals($imageId)) {
                return true;
            }
        }
        return false;
    }
}
