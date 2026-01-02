<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Image\ImageId;
use BarAssistant\Domain\AggregateRoot;
use BarAssistant\Domain\Support\Authors;
use BarAssistant\Domain\Support\Color;
use BarAssistant\Domain\Support\RecordTimestamps;
use BarAssistant\Domain\User\UserId;
use DateTimeImmutable;

final class Ingredient implements AggregateRoot
{
    private ?IngredientId $id = null;

    /** @var IngredientId[] */
    private array $ingredientParts = [];

    /** @var ImageId[] */
    private array $images = [];

    /** @var IngredientPrice[] */
    private array $prices = [];

    private Authors $authors;

    private RecordTimestamps $recordTimestamps;

    private string $name;

    private ?string $description = null;

    private ?float $strength = null;

    private ?string $origin = null;

    private ?Color $color = null;

    public function __construct(
        private BarId $barId,
        private ?IngredientId $parentIngredientId = null,
        private ?MaterializedPath $materializedPath = null,
    ) {
        $this->materializedPath = $materializedPath ?? MaterializedPath::root();
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?IngredientId
    {
        return $this->id;
    }

    public function setId(IngredientId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing ingredient');
        }

        $this->id = $id;

        return $this;
    }

    public function setAsVariantOf(?self $parentIngredient): Ingredient
    {
        if ($parentIngredient !== null && !$parentIngredient->getBarId()->equals($this->getBarId())) {
            throw new DomainException('Parent ingredient must belong to the same bar');
        }

        $this->parentIngredientId = $parentIngredient?->getId();
        $this->materializedPath = $parentIngredient
            ? $parentIngredient->getMaterializedPath()->append($parentIngredient->getId())
            : MaterializedPath::root();

        return $this;
    }

    // public function setParentIngredient(?self $parentIngredient): self
    // {
    //     if ($parentIngredient !== null && !$parentIngredient->getBarId()->equals($this->getBarId())) {
    //         throw new DomainException('Parent ingredient must belong to the same bar');
    //     }

    //     $this->parentIngredientId = $parentIngredient?->getId();

    //     return $this;
    // }

    // public function setMaterializedPath(MaterializedPath $path): self
    // {
    //     $this->materializedPath = $path;

    //     return $this;
    // }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getParentIngredientId(): ?IngredientId
    {
        return $this->parentIngredientId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMaterializedPath(): MaterializedPath
    {
        return $this->materializedPath;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStrength(): ?float
    {
        return $this->strength;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    /**
     * @return IngredientId[]
     */
    public function getIngredientParts(): array
    {
        return $this->ingredientParts;
    }

    public function isComplexIngredient(): bool
    {
        return !empty($this->ingredientParts);
    }

    public function addIngredientPart(Ingredient $partIngredient): self
    {
        if ($partIngredient->isTransient()) {
            throw new DomainException('Ingredient part must have an ID assigned');
        }

        if (!$this->getBarId()->equals($partIngredient->getBarId())) {
            throw new DomainException('All ingredient parts must belong to the same bar');
        }

        if (!$this->isTransient() && $this->getId()->equals($partIngredient->getId())) {
            throw new DomainException('Ingredient cannot contain itself as a part');
        }

        foreach ($this->ingredientParts as $existingPart) {
            if ($existingPart->equals($partIngredient->getId())) {
                return $this;
            }
        }

        $this->ingredientParts[] = $partIngredient->getId();

        return $this;
    }

    public function removeIngredientPart(IngredientId $ingredientId): self
    {
        $this->ingredientParts = array_values(array_filter(
            $this->ingredientParts,
            fn(IngredientId $part) => $part->id !== $ingredientId->id
        ));

        return $this;
    }

    public function removeAllIngredientParts(): self
    {
        $this->ingredientParts = [];

        return $this;
    }

    public function isAncestorOf(self $other): bool
    {
        return $this->materializedPath->isAncestorOf($other->getMaterializedPath());
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
            fn(ImageId $existingImageId) => !$existingImageId->equals($imageId)
        ));

        return $this;
    }

    public function removeAllImages(): self
    {
        $this->images = [];

        return $this;
    }

    /**
     * @return IngredientPrice[]
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    public function addPrice(IngredientPrice $price): self
    {
        $this->prices[] = $price;

        return $this;
    }

    public function removeAllPrices(): self
    {
        $this->prices = [];

        return $this;
    }

    public function getAuthors(): Authors
    {
        return $this->authors;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function updateDetails(
        string $name,
        ?string $description = null,
        ?float $strength = null,
        ?string $origin = null,
        ?Color $color = null,
    ): self {
        if (trim($name) === '') {
            throw new DomainException('Ingredient name cannot be empty');
        }

        if ($strength !== null && ($strength < 0.0 || $strength > 100.0)) {
            throw new DomainException('Ingredient strength must be between 0.0 and 100.0');
        }

        $this->name = $name;
        $this->description = $description;
        $this->strength = $strength;
        $this->origin = $origin;
        $this->color = $color;

        return $this;
    }

    public function wasUpdatedBy(UserId $userId, ?DateTimeImmutable $updatedAt = null): void
    {
        $this->authors = $this->authors->updatedBy($userId);
        $this->recordTimestamps = $updatedAt ? $this->recordTimestamps->updatedAt($updatedAt) : $this->recordTimestamps->updatedNow();
    }

    public function wasCreatedBy(UserId $userId, ?DateTimeImmutable $createdAt = null): void
    {
        $this->authors = Authors::createdBy($userId);
        $this->recordTimestamps = $createdAt ? RecordTimestamps::createdAt($createdAt) : RecordTimestamps::createdNow();
    }
}
