<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail\DTO;

final readonly class CreateCocktail
{
    /**
     * @param string[] $tags
     * @param CocktailIngredient[] $ingredients
     * @param int[] $images
     * @param int[] $utensils
     */
    public function __construct(
        public int $barId,
        public string $name,
        public string $instructions,
        public int $userId,
        public float $dilution,
        public ?string $description,
        public ?string $source,
        public ?string $garnish,
        public ?int $glassId,
        public ?int $methodId,
        public array $tags,
        public array $ingredients,
        public array $images,
        public array $utensils,
        public ?int $parentCocktailId,
        public ?int $year,
        public ?string $author = null,
    ) {
    }
}
