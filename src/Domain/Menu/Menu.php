<?php
declare(strict_types=1);

namespace BarAssistant\Domain\Menu;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Identity;

final class Menu implements Identity
{
    /**
     * @param MenuCategory[] $categories
     */
    private function __construct(
        private MenuId $id,
        private BarId $barId,
        private array $categories,
        private RecordTimestamps $recordTimestamps,
    )
    {
    }

    public function isTransient(): bool
    {
        return false;
    }

    public function getId(): ?MenuId
    {
        return $this->id;
    }

    public function getRecordTimestamps(): RecordTimestamps
    {
        return $this->recordTimestamps;
    }

    public static function create(
        MenuId $id,
        BarId $barId,
    ): self
    {
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
    ): self
    {
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
        $this->categories[] = $category;
        $this->categories = self::sortCategories($this->categories);

        return $this;
    }

    public function removeCategory(MenuCategory $categoryToRemove): self
    {
        $this->categories = array_filter(
            $this->categories,
            static fn(MenuCategory $category) => $category !== $categoryToRemove
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
        usort($categories, static fn(MenuCategory $a, MenuCategory $b) => $a->getSortIndex() <=> $b->getSortIndex());

        return $categories;
    }
}
