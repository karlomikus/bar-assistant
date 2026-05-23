<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Image;

use DateTimeImmutable;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\File;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class Image implements Identity
{
    private ?ImageId $id = null;

    private function __construct(
        private File $file,
        private Authors $authors,
        private RecordTimestamps $recordTimestamps,
        private ?string $copyright = null,
        private int $sort = 0,
    ) {
    }

    public static function create(
        File $file,
        Authors $authors,
        RecordTimestamps $recordTimestamps,
        ?string $copyright = null,
        int $sort = 0,
    ): self {
        return new self(
            file: $file,
            authors: $authors,
            recordTimestamps: $recordTimestamps,
            copyright: $copyright,
            sort: $sort,
        );
    }

    public function updateDetails(
        UserId $userId,
        DateTimeImmutable $updatedAt,
        ?string $copyright = null,
        int $sort = 0,
    ): self {
        $this->authors = $this->authors->updatedBy($userId);
        $this->recordTimestamps = $this->recordTimestamps->updatedAt($updatedAt);
        $this->copyright = $copyright;
        $this->sort = $sort;

        return $this;
    }

    /**
     * Change the file of a temporary image
     */
    public function changeFile(File $newFile): self
    {
        $this->file = $newFile;

        return $this;
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

    /**
     * Returns the copyright information of the image
     */
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

    public function getFile(): File
    {
        return $this->file;
    }
}
