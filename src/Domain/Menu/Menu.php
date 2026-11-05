<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Identity;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;

final class Menu implements Identity
{
    /**
     * @param MenuCategory[] $categories
     */
    private function __construct(
        private readonly MenuId $id,
        private readonly BarId $barId,
        private array $categories,
        private readonly RecordTimestamps $recordTimestamps,
        private bool $enabled = false,
    ) {
    }

    public function isTransient(): bool
    {
        return false;
    }

    public function getId(): MenuId
    {
        return $this->id;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    public static function create(
        MenuId $id,
        BarId $barId,
    ): self {
        return new self(
            id: $id,
            barId: $barId,
            categories: [],
            recordTimestamps: RecordTimestamps::createdNow(),
        );
    }

    /**
     * @param MenuCategory[] $categories
     */
    public static function createWithCategories(
        MenuId $id,
        BarId $barId,
        array $categories,
    ): self {
        self::assertUniqueCategoryNames($categories);

        return new self(
            id: $id,
            barId: $barId,
            categories: self::sortCategories($categories),
            recordTimestamps: RecordTimestamps::createdNow(),
        );
    }

    public function getBarId(): BarId
    {
        return $this->barId;
    }

    /**
     * @return MenuCategory[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function addCategory(MenuCategory $category): self
    {
        foreach ($this->categories as $existing) {
            if ($existing->getName()->equals($category->getName())) {
                throw new DomainException(sprintf('Menu category "%s" already exists', $category->getName()));
            }
        }

        $this->categories[] = $category;
        $this->categories = self::sortCategories($this->categories);

        return $this;
    }

    public function removeCategory(MenuCategory $categoryToRemove): self
    {
        $this->categories = array_filter(
            $this->categories,
            static fn (MenuCategory $category) => $category !== $categoryToRemove
        );
        $this->categories = array_values($this->categories);

        return $this;
    }

    public function clearAllCategories(): self
    {
        $this->categories = [];

        return $this;
    }

    /**
     * @param MenuCategory[] $categories
     * @return MenuCategory[]
     */
    private static function sortCategories(array $categories): array
    {
        usort($categories, static fn (MenuCategory $a, MenuCategory $b) => $a->getSortIndex() <=> $b->getSortIndex());

        return $categories;
    }

    /**
     * Mirrors the database unique constraint on (menu_id, name).
     *
     * @param MenuCategory[] $categories
     */
    private static function assertUniqueCategoryNames(array $categories): void
    {
        $seen = [];
        foreach ($categories as $category) {
            $name = $category->getName()->toString();
            if (isset($seen[$name])) {
                throw new DomainException(sprintf('Menu category "%s" already exists', $name));
            }
            $seen[$name] = true;
        }
    }
}
