<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Image;

use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\RecordTimestamps;

final class Image implements Identity
{
    private ?ImageId $id = null;

    private function __construct(
        private readonly string $path,
        private readonly string $fileExtension,
        private readonly Authors $authors,
        private readonly RecordTimestamps $recordTimestamps,
        private readonly ?string $placeholderHash = null,
        private readonly ?string $copyright = null,
        private readonly int $sort = 0,
    ) {
        if (trim($path) === '') {
            throw new DomainException('Image filepath cannot be empty');
        }
    }

    public static function create(
        string $path,
        string $fileExtension,
        Authors $authors,
        RecordTimestamps $recordTimestamps,
        ?string $placeholderHash = null,
        ?string $copyright = null,
        int $sort = 0,
    )
    {
        return new self(
            path: $path,
            fileExtension: $fileExtension,
            authors: $authors,
            recordTimestamps: $recordTimestamps,
            placeholderHash: $placeholderHash,
            copyright: $copyright,
            sort: $sort,
        );
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function setId(ImageId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing image');
        }

        $this->id = $id;

        return $this;
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPlaceholderHash(): ?string
    {
        return $this->placeholderHash;
    }

    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }
}
