<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\AmountWithUnits;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Image\ImageId;

final class Glass implements Identity
{
    private ?GlassId $id = null;

    /**
     * @param ImageId[] $images
     */
    private function __construct(
        private readonly BarId $barId,
        private Name $name,
        private RecordTimestamps $recordTimestamps,
        private ?AmountWithUnits $volume = null,
        private array $images = [],
    ) {
    }

    /**
     * @param ImageId[] $images
     */
    public static function create(
        BarId $barId,
        Name $name,
        RecordTimestamps $recordTimestamps,
        ?AmountWithUnits $volume = null,
        array $images = [],
    ): self {
        return new self(
            barId: $barId,
            name: $name,
            recordTimestamps: $recordTimestamps,
            volume: $volume,
            images: $images,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?GlassId
    {
        return $this->id;
    }

    public function setId(GlassId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing glass');
        }

        $this->id = $id;

        return $this;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function getVolume(): ?AmountWithUnits
    {
        return $this->volume;
    }

    /**
     * @return ImageId[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    public function addImage(ImageId $imageId): self
    {
        foreach ($this->images as $existingImageId) {
            if ($existingImageId->equals($imageId)) {
                return $this;
            }
        }

        $this->images[] = $imageId;

        return $this;
    }

    public function removeImage(ImageId $imageId): self
    {
        $this->images = array_values(array_filter(
            $this->images,
            static fn (ImageId $existingImageId) => !$existingImageId->equals($imageId)
        ));

        return $this;
    }

    public function removeAllImages(): self
    {
        $this->images = [];

        return $this;
    }

    public function updateDetails(
        Name $name,
        ?AmountWithUnits $volume = null,
    ): self {
        if ($this->isTransient()) {
            throw new DomainException('Cannot update details of a transient glass');
        }

        $this->name = $name;
        $this->volume = $volume;
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        return $this;
    }
}
