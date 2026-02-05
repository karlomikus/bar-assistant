<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Cocktail;

use DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Common\ABV;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Dilution;

final class Cocktail implements Identity
{
    private ?CocktailId $id = null;

    /**
     * @param CocktailIngredient[] $ingredients
     */
    private function __construct(
        private Name $name,
        private string $instructions,
        private ?string $garnish = null,
        private ?Dilution $dilution = null,
        private array $ingredients = [],
        private ?CocktailId $variantOf = null,
    ) {
    }

    /**
     * @param CocktailIngredient[] $ingredients
     */
    public static function create(
        Name $name,
        string $instructions,
        ?string $garnish = null,
        ?Dilution $dilution = null,
        array $ingredients = [],
    ): self {
        return new self(
            name: $name,
            instructions: $instructions,
            garnish: $garnish,
            dilution: $dilution,
            ingredients: $ingredients,
        );
    }

    public function getId(): ?CocktailId
    {
        return $this->id;
    }

    public function isTransient(): bool
    {
        return $this->id === null;
    }

    public function setId(CocktailId $id): self
    {
        if ($this->isTransient() === false) {
            throw new DomainException('Cannot change the ID of an existing cocktail');
        }

        $this->id = $id;

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function getGarnish(): ?string
    {
        return $this->garnish;
    }

    public function isVariant(): bool
    {
        return $this->variantOf !== null;
    }

    public function getABV(): ABV
    {
        if ($this->dilution === null) {
            return ABV::from(0.0);
        }

        $amountUsed = 0.0;
        foreach ($this->ingredients as $ingredient) {
            $amountUsed += $ingredient->amountWithUnits->amountMin;
        }

        $alcoholVolume = floatval(array_reduce(
            $this->ingredients,
            static fn ($carry, $item) => (($item->amountWithUnits->amountMin * $item->abv->toFloat()) / 100) + $carry,
        ));

        $afterDilution = ($amountUsed * $this->dilution->toDecimal()) + $amountUsed;

        if ($afterDilution <= 0) {
            return ABV::from(0.0);
        }

        return ABV::from(round(($alcoholVolume / $afterDilution) * 100, 2));
    }

    public function addIngredient(CocktailIngredient $ingredient): self
    {
        foreach ($this->ingredients as $existingIngredient) {
            if ($existingIngredient->ingredientId->equals($ingredient->ingredientId)) {
                return $this;
            }
        }

        $this->ingredients[] = $ingredient;

        return $this;
    }

    /**
     * @return CocktailIngredient[]
     */
    public function getIngredients(): array
    {
        return $this->ingredients;
    }
}
