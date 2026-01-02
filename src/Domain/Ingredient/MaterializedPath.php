<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

/**
 * Represents computed materialized path for an ingredient in the ingredient hierarchy.
 *
 * Root ingredients have an empty path represented as an empty string.
 * Non-root ingredients have a path consisting of ancestor ingredient IDs separated by SEPARATOR and ending with SEPARATOR.
 */
final readonly class MaterializedPath
{
    private const SEPARATOR = '/';
    private const TAXONOMY_MAX_DEPTH = 10;

    /**
     * @param array<IngredientId> $basePath
     */
    private function __construct(private array $basePath = [])
    {
    }

    public static function root(): self
    {
        return new self();
    }

    public static function fromString(?string $materializedPath): self
    {
        if ($materializedPath === null) {
            return new self();
        }

        $basePath = array_map(
            fn(int $id) => new IngredientId($id),
            array_map('intval', array_filter(explode(self::SEPARATOR, $materializedPath)))
        );

        return new self($basePath);
    }

    public function append(int $id): self
    {
        if ($this->getDepth() === self::TAXONOMY_MAX_DEPTH) {
            throw new \Exception('Ingredient has too many descendants, max depth is ' . self::TAXONOMY_MAX_DEPTH);
        }

        $newPath = $this->basePath;
        $newPath[] = $id;

        return new self($newPath);
    }

    public function isRoot(): bool
    {
        return $this->getDepth() === 0;
    }

    public function getDepth(): int
    {
        return count($this->basePath);
    }

    /**
     * @return IngredientId[]
     */
    public function getAncestorIds(): array
    {
        return $this->basePath;
    }

    public function getParentId(): ?IngredientId
    {
        $ancestors = $this->getAncestorIds();
        if (empty($ancestors)) {
            return null;
        }

        return end($ancestors);
    }

    public function isAncestorOf(self $other): bool
    {
        if ($this->isRoot()) {
            return false;
        }

        return in_array($this->getParentId(), $other->getAncestorIds(), true);
    }

    public function isDescendantOf(self $other): bool
    {
        return $other->isAncestorOf($this);
    }

    public function toString(): string
    {
        if (empty($this->basePath)) {
            return '';
        }

        return implode(self::SEPARATOR, $this->basePath) . self::SEPARATOR;
    }
}
