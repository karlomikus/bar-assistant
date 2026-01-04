<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

use BarAssistant\Domain\Ingredient\IngredientAncestorPath;

/**
 * Result object containing ingredient data with its hierarchical path.
 *
 * Example:
 * For "Islay Scotch" the ancestorPath might be:
 * [
 *   {id: 1, name: "Spirits"},
 *   {id: 5, name: "Whiskey"},
 *   {id: 12, name: "Scotch"}
 * ]
 */
final readonly class IngredientWithPathResult
{
    /**
     * @param IngredientPathItem[] $ancestorPath Ordered list of ancestors from root to immediate parent
     * @param string $pathString Formatted string path (e.g., "Spirits > Whiskey > Scotch")
     */
    public function __construct(
        public IngredientResult $ingredient,
        public array $ancestorPath,
        public string $pathString,
    ) {
    }

    public static function fromIngredientAncestorPath(IngredientAncestorPath $ancestorPath): self
    {
        $pathItems = [];
        foreach ($ancestorPath->getAncestors() as $ancestor) {
            $pathItems[] = new IngredientPathItem(
                id: $ancestor->getId()->id,
                name: $ancestor->getName(),
            );
        }

        return new self(
            ingredient: IngredientResult::fromIngredient($ancestorPath->getIngredient()),
            ancestorPath: $pathItems,
            pathString: implode(' > ', array_map(fn($item) => $item->name, $pathItems)),
        );
    }
}
