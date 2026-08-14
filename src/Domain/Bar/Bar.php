<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use Brick\Money\Currency;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Slug;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Common\Traits\HasImages;
use BarAssistant\Domain\Exception\DomainException;

final class Bar implements Identity
{
    use HasImages;

    private ?BarId $id = null;
    private ?Slug $slug = null;

    private function __construct(
        private Name $name,
        private Authors $authors,
        private RecordTimestamps $recordTimestamps,
        private ?BarSettings $settings = null,
        private bool $isPublic = false,
        private ?string $subtitle = null,
        private ?string $description = null,
        private BarStatus $status = BarStatus::Active,
    ) {
        if ($settings === null) {
            $this->settings = BarSettings::createDefault();
        }
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?BarId
    {
        return $this->id;
    }

    public function setId(BarId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing bar');
        }

        $this->id = $id;

        return $this;
    }

    public function getSlug(): ?Slug
    {
        return $this->slug;
    }

    public function setSlug(Slug $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public static function create(
        Name $name,
        Authors $authors,
        RecordTimestamps $recordTimestamps,
        ?BarSettings $settings = null,
        ?string $subtitle = null,
        ?string $description = null,
    ): Bar {
        return new self(
            name: $name,
            authors: $authors,
            recordTimestamps: $recordTimestamps,
            settings: $settings,
            subtitle: $subtitle,
            description: $description,
        );
    }

    /**
     * Returns the bar name
     */
    public function getName(): Name
    {
        return $this->name;
    }

    /**
     * Returns the bar subtitle
     */
    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /**
     * Returns the bar status
     */
    public function getStatus(): BarStatus
    {
        return $this->status;
    }

    /**
     * Returns the bar description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Is the bar publically available
     */
    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * Returns the default units for the bar
     */
    public function getDefaultUnits(): ?Unit
    {
        return $this->settings?->defaultUnits;
    }

    /**
     * Returns the default currency for the bar
     */
    public function getDefaultCurrency(): ?Currency
    {
        return $this->settings?->defaultCurrency;
    }

    public function getAuthors(): Authors
    {
        return $this->authors;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function isInviteCodeEnabled(): bool
    {
        return $this->settings->isInviteCodeEnabled ?? false;
    }

    public function makePublic(): self
    {
        $this->isPublic = true;

        return $this;
    }

    public function makePrivate(): self
    {
        $this->isPublic = false;

        return $this;
    }

    public function updateSettings(BarSettings $settings): self
    {
        if ($this->isTransient()) {
            throw new DomainException('Cannot update settings of a transient bar');
        }

        $this->settings = $settings;

        return $this;
    }

    public function updateDetails(
        Name $name,
        UserId $updatedBy,
        ?string $subtitle = null,
        ?string $description = null,
    ): self {
        if ($this->isTransient()) {
            throw new DomainException('Cannot update details of a transient bar');
        }

        $this->name = $name;
        $this->subtitle = $subtitle;
        $this->description = $description;
        $this->authors = $this->authors->updatedBy($updatedBy);
        $this->recordTimestamps = $this->recordTimestamps->updatedNow();

        return $this;
    }
}
