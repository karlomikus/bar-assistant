<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

final readonly class MaterializedPath
{
    private const SEPARATOR = '/';

    /**
     * @param null|array<int> $basePath
     */
    private function __construct(private ?array $basePath = null)
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

    public function isRoot(): bool
    {
        return $this->basePath === null;
    }

    public function append(int $id): self
    {
        $newPath = $this->basePath;
        $newPath[] = $id;

        return new self($newPath);
    }

    /**
     * @return array<int> $basePath
     */
    public function toArray(): array
    {
        return $this->basePath ?? [];
    }

    public function toStringPath(): string
    {
        return implode(self::SEPARATOR, $this->basePath) . '/';
    }
}
