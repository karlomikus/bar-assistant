<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Common\Price;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Ingredient\IngredientId;
use DomainException;

final readonly class MenuItem
{
    private function __construct(
        private Price $price,
        private int $sortIndex,
        private ?CocktailId $cocktailId = null,
        private ?IngredientId $ingredientId = null,
    )
    {
        if ($cocktailId === null && $ingredientId === null) {
            throw new DomainException('Menu item must reference either a cocktail or an ingredient');
        }

        if ($cocktailId !== null && $ingredientId !== null) {
            throw new DomainException('Menu item cannot reference both a cocktail and an ingredient');
        }

        if ($price->getAsMinor() <= 0) {
            throw new DomainException('Menu item price must be greater than zero');
        }

        if ($sortIndex < 0) {
            throw new DomainException('Sort index must be non-negative');
        }
    }

    public static function forCocktail(
        CocktailId $cocktailId,
        Price $price,
        int $sortIndex,
    ): self
    {
        return new self(
            price: $price,
            sortIndex: $sortIndex,
            cocktailId: $cocktailId,
        );
    }

    public static function forIngredient(
        IngredientId $ingredientId,
        Price $price,
        int $sortIndex,
    ): self
    {
        return new self(
            price: $price,
            sortIndex: $sortIndex,
            ingredientId: $ingredientId,
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

    public function withPrice(Price $price): self
    {
        return new self(
            price: $price,
            sortIndex: $this->sortIndex,
            cocktailId: $this->cocktailId,
            ingredientId: $this->ingredientId,
        );
    }

    public function withSortIndex(int $sortIndex): self
    {
        return new self(
            price: $this->price,
            sortIndex: $sortIndex,
            cocktailId: $this->cocktailId,
            ingredientId: $this->ingredientId,
        );
    }
}
