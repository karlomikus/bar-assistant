<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Common\Unit;
use Brick\Money\Currency;

final class Bar implements Identity
{
    private ?BarId $id = null;

    /**
     * @param IngredientInventoryItem[] $ingredientInventory
     */
    private function __construct(
        private Name $name,
        private Authors $authors,
        private RecordTimestamps $recordTimestamps,
        private array $images = [],
        private bool $isPublic = false,
        private bool $isInviteCodeEnabled = false,
        private ?string $subtitle = null,
        private ?string $description = null,
        private ?Unit $defaultUnits = null,
        private ?Currency $defaultCurrency = null,
        private array $ingredientInventory = [],
    ) {
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

    public static function create(
        Name $name,
        Authors $authors,
        RecordTimestamps $recordTimestamps,
        array $ingredientInventory = []
    ): Bar {
        return new self(
            name: $name,
            authors: $authors,
            recordTimestamps: $recordTimestamps,
            ingredientInventory: $ingredientInventory,
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
        return $this->isPublic();
    }

    /**
     * Returns the default units for the bar
     */
    public function getDefaultUnits(): ?Unit
    {
        return $this->defaultUnits;
    }

    /**
     * Returns the default currency for the bar
     */
    public function getDefaultCurrency(): ?Currency
    {
        return $this->defaultCurrency;
    }

    /**
     * Returns a list of all ingredients that bar has in inventory
     *
     * @return IngredientInventoryItem[]
     */
    public function getIngredientInventory(): array
    {
        return $this->ingredientInventory;
    }

    public function putIngredientInStock(IngredientId $ingredientId): self
    {
        if ($this->hasIngredientInStock($ingredientId)) {
            return $this;
        }

        $this->ingredientInventory[] = new IngredientInventoryItem($ingredientId, IngredientInventoryStatus::InStock);

        return $this;
    }

    public function removeIngredientFromStock(IngredientId $ingredientId): self
    {
        if (!$this->hasIngredientInStock($ingredientId)) {
            return $this;
        }

        $this->ingredientInventory = array_filter(
            $this->ingredientInventory,
            static fn (IngredientInventoryItem $item) => !$item->ingredientId->equals($ingredientId)
        );

        return $this;
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getVariantIngredients(): array
    {
        return array_filter(
            $this->ingredientInventory,
            static fn (IngredientInventoryItem $item) => $item->isInStockAsVariant()
        );
    }

    /**
     * @return IngredientInventoryItem[]
     */
    public function getInStockIngredients(): array
    {
        return array_filter(
            $this->ingredientInventory,
            static fn (IngredientInventoryItem $item) => $item->isInStock()
        );
    }

    /**
     * Checks if the ingredient is actually in stock, meaning it
     * will not match variants and complex ingredients.
     */
    public function hasIngredientInStock(IngredientId $ingredientId): bool
    {
        return array_any(
            $this->getInStockIngredients(),
            static fn($existingInventoryItem) => $existingInventoryItem->ingredientId->equals($ingredientId)
        );
    }
}
