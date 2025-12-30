<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Image;

use BarAssistant\Domain\Bar\UserId;
use BarAssistant\Domain\Exception\DomainException;

final class Image
{
    private ?ImageId $id = null;

    public function __construct(
        private string $filepath,
        private string $placeholderHash,
        private UserId $createdUserId,
        private ?string $copyright = null,
        private int $sort = 0,
        private ?UserId $updatedUserId = null,
    ) {
        if (trim($filepath) === '') {
            throw new DomainException('Image filepath cannot be empty');
        }
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?ImageId
    {
        return $this->id;
    }

    public function setId(ImageId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing image');
        }

        $this->id = $id;

        return $this;
    }

    public function getFilepath(): string
    {
        return $this->filepath;
    }

    public function getPlaceholderHash(): string
    {
        return $this->placeholderHash;
    }

    public function getCreatedUserId(): UserId
    {
        return $this->createdUserId;
    }
}
