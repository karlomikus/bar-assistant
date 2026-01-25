<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\User\UserId;

final class Member implements Identity
{
    private ?MemberId $id = null;

    /**
     * @param ShoppingListItem[] $shoppingListIngredients
     */
    private function __construct(
        private UserId $userId,
        private BarId $barId,
        private array $shoppingListIngredients = [],
    )
    {
    }

    public function getId(): ?MemberId
    {
        return $this->id;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    /**
     * @return ShoppingListItem[]
     */
    public function getShoppingListIngredients(): array
    {
        return $this->shoppingListIngredients;
    }

    public function isIngredientOnShoppingList(IngredientId $ingredientId): bool
    {
        return array_any(
            $this->shoppingListIngredients,
            static fn(ShoppingListItem $existingShoppingListItem): bool => $existingShoppingListItem->ingredientId->equals($ingredientId)
        );
    }

    public function addIngredientToShoppingList(IngredientId $ingredientId, int $quantity): self
    {
        // Replace item with increased quantity
        if ($this->isIngredientOnShoppingList($ingredientId)) {
            $this->removeIngredientFromShoppingList($ingredientId);
            $quantity += 1;
        }

        $this->shoppingListIngredients[] = ShoppingListItem::create($ingredientId, $quantity);

        return $this;
    }

    public function removeIngredientFromShoppingList(IngredientId $ingredientId): self
    {
        if (!$this->isIngredientOnShoppingList($ingredientId)) {
            return $this;
        }

        $this->shoppingListIngredients = array_filter(
            $this->shoppingListIngredients,
            static fn (ShoppingListItem $item): bool => !$item->ingredientId->equals($ingredientId)
        );

        return $this;
    }
}
