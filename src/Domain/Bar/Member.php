<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Ingredient\IngredientId;

final class Member implements Identity
{
    private ?MemberId $id = null;

    /**
     * @param ShoppingListItem[] $shoppingListIngredients
     */
    private function __construct(
        private UserId $userId,
        private BarId $barId,
        private MemberRole $role,
        private array $shoppingListIngredients = [],
    ) {
    }

    /**
     * @param ShoppingListItem[] $shoppingListIngredients
     */
    public static function create(UserId $userId, BarId $barId, MemberRole $role, array $shoppingListIngredients = []): self
    {
        return new self(
            userId: $userId,
            barId: $barId,
            role: $role,
            shoppingListIngredients: $shoppingListIngredients,
        );
    }

    public function getId(): ?MemberId
    {
        return $this->id;
    }

    public function setId(MemberId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing member');
        }

        $this->id = $id;

        return $this;
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

    public function getRole(): MemberRole
    {
        return $this->role;
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
            static fn (ShoppingListItem $existingShoppingListItem): bool => $existingShoppingListItem->ingredientId->equals($ingredientId)
        );
    }

    public function addIngredientToShoppingList(IngredientId $ingredientId, int $quantity): self
    {
        $this->removeIngredientFromShoppingList($ingredientId);

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
