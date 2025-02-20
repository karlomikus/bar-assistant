<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

use Kami\Cocktail\Exceptions\IngredientPathTooDeepException;

final readonly class MaterializedPath
{
    private const SEPARATOR = '/';
    private const TAXONOMY_MAX_DEPTH = 10;

    /**
     * @param array<int> $basePath
     */
    private function __construct(private array $basePath = [])
    {
    }

    public static function fromString(?string $materializedPath): self
    {
        if ($materializedPath === null) {
            return new self();
        }

        $basePath = array_map('intval', array_filter(explode(self::SEPARATOR, $materializedPath)));

        return new self($basePath);
    }

    public function append(int $id): self
    {
        if ($this->getDepth() === self::TAXONOMY_MAX_DEPTH) {
            throw new IngredientPathTooDeepException('Ingredient has too many descendants, max depth is ' . self::TAXONOMY_MAX_DEPTH);
        }

        $newPath = $this->basePath;
        $newPath[] = $id;

        return new self($newPath);
    }

    public function getDepth(): int
    {
        return count($this->basePath);
    }

    /**
     * @return array<int> $basePath
     */
    public function toArray(): array
    {
        return $this->basePath;
    }

    public function toStringPath(): string
    {
        if (empty($this->basePath)) {
            return '';
        }

        return implode(self::SEPARATOR, $this->basePath) . '/';
    }
}
