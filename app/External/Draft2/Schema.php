<?php

declare(strict_types=1);

namespace Kami\Cocktail\External\Draft2;

use JsonSerializable;
use Kami\Cocktail\Models\Cocktail as CocktailModel;

readonly class Schema implements JsonSerializable
{
    /**
     * @param array<Ingredient> $ingredients
     */
    private function __construct(
        public Cocktail $cocktail,
        public array $ingredients,
    ) {
    }

    public static function fromCocktailModel(CocktailModel $model, bool $useFullURL = false): self
    {
        $ingredients = [];
        foreach ($model->ingredients as $cocktailIngredient) {
            $ingredients[] = Ingredient::fromModel($cocktailIngredient->ingredient);
        }

        return new self(
            Cocktail::fromModel($model, $useFullURL),
            $ingredients,
        );
    }

    public function toArray(): array
    {
        return [
            'recipe' => $this->cocktail->toArray(),
            'ingredients' => array_map(fn ($model) => $model->toArray(), $this->ingredients),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
