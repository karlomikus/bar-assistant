<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Image;

use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\AggregateRoot;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\RecordTimestamps;

final class Image implements AggregateRoot
{
    private ?ImageId $id = null;

    public function __construct(
        private string $filepath,
        private string $placeholderHash,
        private Authors $authors,
        private RecordTimestamps $recordTimestamps,
        private ?string $copyright = null,
        private int $sort = 0,
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

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getAuthors(): Authors
    {
        return $this->authors;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
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
}
