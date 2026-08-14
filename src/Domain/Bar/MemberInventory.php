<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Exception\DomainException;

final class MemberInventory implements Identity
{
    private ?MemberInventoryId $id = null;

    /**
     * @param IngredientInventoryItem[] $ingredients
     */
    private function __construct(
        private MemberId $memberId,
        private Name $name,
        private array $ingredients = [],
    ) {
    }

    /**
     * @param IngredientInventoryItem[] $ingredients
     */
    public static function create(
        MemberId $memberId,
        Name $name,
        array $ingredients = [],
    ): self {
        return new self(
            memberId: $memberId,
            name: $name,
            ingredients: $ingredients,
        );
    }

    public function getId(): ?MemberInventoryId
    {
        return $this->id;
    }

    public function setId(MemberInventoryId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing member inventory');
        }

        $this->id = $id;

        return $this;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getMemberId(): MemberId
    {
        return $this->memberId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function rename(Name $newName, UserId $updatedBy): self
    {
        if ($this->isTransient()) {
            throw new DomainException('Cannot rename a transient member inventory');
        }

        $this->name = $newName;

        return $this;
    }

    public function putIngredient(IngredientId $ingredientId, IngredientInventoryStatus $status): self
    {
        $this->removeIngredient($ingredientId);

        $this->ingredients[] = new IngredientInventoryItem(
            ingredientId: $ingredientId,
            ingredientStatus: $status,
        );

        return $this;
    }

    public function removeIngredient(IngredientId $ingredientId): self
    {
        $this->ingredients = array_values(array_filter(
            $this->ingredients,
            static fn (IngredientInventoryItem $inventoryIngredient): bool => !$inventoryIngredient->ingredientId->equals($ingredientId)
        ));

        return $this;
    }
}
