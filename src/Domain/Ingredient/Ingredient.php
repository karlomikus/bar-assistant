<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Support\Color;

final class Ingredient
{
    private ?IngredientId $id = null;

    private ?IngredientId $parentIngredientId = null;

    private MaterializedPath $materializedPath;

    /** @var IngredientId[] */
    private array $ingredientParts = [];

    /**
     * @param IngredientId[] $ingredientParts
     */
    public function __construct(
        private BarId $barId,
        private string $name,
        private ?string $description,
        private ?float $strength,
        private ?string $origin,
        private ?Color $color,
    ) {
        if (trim($name) === '') {
            throw new DomainException('Ingredient name cannot be empty');
        }

        $this->materializedPath = MaterializedPath::root();
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getId(): ?IngredientId
    {
        return $this->id;
    }

    public function withId(IngredientId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing ingredient');
        }

        $this->id = $id;

        return $this;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMaterializedPath(): MaterializedPath
    {
        return $this->materializedPath;
    }

    public function isRoot(): bool
    {
        return $this->parentIngredientId === null;
    }

    public function isComplexIngredient(): bool
    {
        return !empty($this->ingredientParts);
    }

    public function addIngredientPart(Ingredient $partIngredient): self
    {
        if ($this->barId->id !== $partIngredient->getBarId()->id) {
            throw new DomainException('All ingredient parts must belong to the same bar');
        }

        if ($this->id !== null && $this->id->id === $partIngredient->getId()->id) {
            throw new DomainException('Ingredient cannot contain itself as a part');
        }

        // Check if this part already exists
        foreach ($this->ingredientParts as $existingPart) {
            if ($existingPart->id === $partIngredient->getId()) {
                throw new DomainException('This ingredient part already exists');
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

    public function setAsVariantOf(self $parentIngredient): self
    {
        if ($this->isTransient()) {
            throw new DomainException('Ingredient must have an ID before setting a parent ingredient');
        }

        if ($this->getId()->equals($parentIngredient->getId())) {
            throw new DomainException('Ingredient cannot be a variant of itself');
        }

        if ($this->isAncestorOf($parentIngredient)) {
            throw new DomainException('Cannot set parent ingredient to a descendant');
        }

        $newPath = $parentIngredient->getMaterializedPath()->append($this->getId()->id);

        $this->parentIngredientId = $parentIngredient->getId();
        $this->materializedPath = $newPath;

        return $this;
    }

    public function makeRoot(): self
    {
        $this->parentIngredientId = null;
        $this->materializedPath = MaterializedPath::root();

        return $this;
    }

    public function isAncestorOf(self $other): bool
    {
        return $this->materializedPath->isAncestorOf($other->materializedPath);
    }

    public function isDescendantOf(self $other): bool
    {
        return $this->materializedPath->isDescendantOf($other->materializedPath);
    }
}
