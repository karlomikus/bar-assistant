<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

use BarAssistant\Domain\Exception\DomainException;

/**
 * Represents computed materialized path for an ingredient in the ingredient hierarchy.
 *
 * Root ingredients have an empty path represented as an empty string.
 * Non-root ingredients have a path consisting of ancestor ingredient IDs separated by SEPARATOR and ending with SEPARATOR.
 */
final readonly class MaterializedPath
{
    private const string SEPARATOR = '/';
    private const int TAXONOMY_MAX_DEPTH = 10;

    /**
     * @param IngredientId[] $basePath
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
            static fn (int $id) => new IngredientId($id),
            array_map(intval(...), array_filter(explode(self::SEPARATOR, $materializedPath)))
        );

        return new self($basePath);
    }

    public function append(IngredientId $id): self
    {
        if ($this->getDepth() === self::TAXONOMY_MAX_DEPTH) {
            throw new DomainException('Ingredient has too many descendants, max depth is ' . self::TAXONOMY_MAX_DEPTH);
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
        if ($this->isRoot() || $this->equals($other) || $this->getParentId() === null) {
            return false;
        }

        $parentId = $this->getParentId();

        return array_any($other->getAncestorIds(), $parentId->equals(...));
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

    public function getRelativePath(self $basePath): self
    {
        $relativeBasePath = $this->basePath;

        foreach ($basePath->getAncestorIds() as $ancestorId) {
            if (empty($relativeBasePath)) {
                break;
            }

            if ($relativeBasePath[0]->equals($ancestorId)) {
                array_shift($relativeBasePath);
            } else {
                break;
            }
        }

        return new self($relativeBasePath);
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }
}
