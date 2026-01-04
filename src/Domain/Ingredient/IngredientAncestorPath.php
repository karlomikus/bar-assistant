<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Ingredient;

/**
 * Represents the hierarchical path of an ingredient with its ancestors.
 *
 * Example: For "Islay Scotch" the path might be: Spirits > Whiskey > Scotch > Islay Scotch
 */
final readonly class IngredientAncestorPath
{
    /**
     * @param Ingredient[] $ancestors Ordered list of ancestors from root to immediate parent
     * @param Ingredient $ingredient The ingredient itself
     */
    private function __construct(
        private array $ancestors,
        private Ingredient $ingredient,
    ) {
    }

    public static function from(Ingredient $ingredient, array $ancestors): self
    {
        return new self($ancestors, $ingredient);
    }

    /**
     * Get all ancestors ordered from root to immediate parent
     * 
     * @return Ingredient[]
     */
    public function getAncestors(): array
    {
        return $this->ancestors;
    }

    /**
     * Get the ingredient
     */
    public function getIngredient(): Ingredient
    {
        return $this->ingredient;
    }

    /**
     * Get the full path including the ingredient itself
     *
     * @return Ingredient[]
     */
    public function getFullPath(): array
    {
        return [...$this->ancestors, $this->ingredient];
    }

    /**
     * Get formatted path as string with separator
     *
     * @param string $separator Separator between ingredient names (default: " > ")
     */
    public function toStringPath(string $separator = ' > '): string
    {
        $names = array_map(fn(Ingredient $ing) => $ing->getName(), $this->getFullPath());

        return implode($separator, $names);
    }
}
