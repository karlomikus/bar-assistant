<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use DomainException;
use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;

final readonly class MenuItem
{
    private function __construct(
        private Price $price,
        private int $sortIndex,
        private ?CocktailId $cocktailId = null,
        private ?IngredientId $ingredientId = null,
        private bool $barInventoryAware = false,
    ) {
        if ($cocktailId === null && $ingredientId === null) {
            throw new DomainException('Menu item must reference either a cocktail or an ingredient');
        }

        if ($cocktailId !== null && $ingredientId !== null) {
            throw new DomainException('Menu item cannot reference both a cocktail and an ingredient');
        }

        if ($price->getAsMinor() < 0) {
            throw new DomainException('Menu item price must be non-negative');
        }

        if ($sortIndex < 0) {
            throw new DomainException('Sort index must be non-negative');
        }
    }

    public static function forCocktail(
        CocktailId $cocktailId,
        Price $price,
        int $sortIndex,
        bool $barInventoryAware = false,
    ): self {
        return new self(
            price: $price,
            sortIndex: $sortIndex,
            cocktailId: $cocktailId,
            barInventoryAware: $barInventoryAware,
        );
    }

    public static function forIngredient(
        IngredientId $ingredientId,
        Price $price,
        int $sortIndex,
        bool $barInventoryAware = false,
    ): self {
        return new self(
            price: $price,
            sortIndex: $sortIndex,
            ingredientId: $ingredientId,
            barInventoryAware: $barInventoryAware,
        );
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    public function getCocktailId(): ?CocktailId
    {
        return $this->cocktailId;
    }

    public function getIngredientId(): ?IngredientId
    {
        return $this->ingredientId;
    }

    public function isCocktail(): bool
    {
        return $this->cocktailId !== null;
    }

    public function isIngredient(): bool
    {
        return $this->ingredientId !== null;
    }

    public function isBarInventoryAware(): bool
    {
        return $this->barInventoryAware;
    }

    public function withPrice(Price $price): self
    {
        return new self(
            price: $price,
            sortIndex: $this->sortIndex,
            cocktailId: $this->cocktailId,
            ingredientId: $this->ingredientId,
            barInventoryAware: $this->barInventoryAware,
        );
    }

    public function withSortIndex(int $sortIndex): self
    {
        return new self(
            price: $this->price,
            sortIndex: $sortIndex,
            cocktailId: $this->cocktailId,
            ingredientId: $this->ingredientId,
            barInventoryAware: $this->barInventoryAware,
        );
    }
}
