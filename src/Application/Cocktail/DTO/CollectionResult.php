<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

use BarAssistant\Domain\Cocktail\CocktailCollection;

final readonly class CollectionResult
{
    /**
     * @param int[] $cocktailIds
     */
    public function __construct(
        public int $id,
        public int $barId,
        public int $memberId,
        public string $name,
        public ?string $description,
        public bool $isBarShared,
        public array $cocktailIds,
    ) {
    }

    public static function fromCollection(CocktailCollection $collection): self
    {
        return new self(
            id: $collection->getId()->value ?? 0,
            barId: $collection->getBarId()->value,
            memberId: $collection->getMemberId()->value,
            name: $collection->getName()->toString(),
            description: $collection->getDescription(),
            isBarShared: $collection->isBarShared(),
            cocktailIds: array_map(
                static fn (\BarAssistant\Domain\Cocktail\CocktailId $cocktailId): int => $cocktailId->value,
                $collection->getCocktailIds(),
            ),
        );
    }
}
