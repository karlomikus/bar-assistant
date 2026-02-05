<?php

declare(strict_types=1);

namespace BarAssistant\Application\Ingredient\DTO;

use BarAssistant\Domain\Ingredient\Ingredient;

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
final readonly class IngredientHierarchyResult
{
    /**
     * @param IngredientPathItem[] $ancestors Ordered list of ancestors from root to immediate parent
     * @param string $pathToSelf Formatted string path (e.g., "Spirits > Whiskey > Scotch")
     */
    private function __construct(
        public string $pathToSelf,
        public array $ancestors,
    ) {
    }

    /**
     * @param Ingredient[] $ancestors
     */
    public static function fromAncestors(array $ancestors): self
    {
        $pathItems = [];
        foreach ($ancestors as $ancestor) {
            if ($ancestor->isTransient()) {
                continue;
            }

            $pathItems[] = new IngredientPathItem(
                id: $ancestor->getId()->value,
                name: $ancestor->getName()->toString(),
            );
        }

        return new self(
            ancestors: $pathItems,
            pathToSelf: implode(' > ', array_map(static fn ($item) => $item->name, $pathItems)),
        );
    }
}
