<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Ingredient;

use Illuminate\Http\Request;

readonly class Ingredient
{
    /**
     * @param array<int> $images
     * @param array<int> $complexIngredientParts
     * @param array<Price> $prices
     */
    public function __construct(
        public int $barId,
        public string $name,
        public int $userId,
        public ?int $ingredientCategoryId = null,
        public float $strength = 0.0,
        public ?string $description = null,
        public ?string $origin = null,
        public ?string $color = null,
        public ?int $parentIngredientId = null,
        public array $images = [],
        public array $complexIngredientParts = [],
        public array $prices = [],
    ) {
    }

    public static function fromIlluminateRequest(Request $request, int $barId): self
    {
        $formPrices = $request->input('prices', []);

        $prices = [];
        if (is_array($formPrices)) {
            foreach ($formPrices as $price) {
                $prices[] = Price::fromArray($price);
            }
        }

        return new self(
            $barId,
            $request->input('name'),
            $request->user()->id,
            $request->filled('ingredient_category_id') ? $request->integer('ingredient_category_id') : null,
            $request->float('strength'),
            $request->input('description'),
            $request->input('origin'),
            $request->input('color'),
            $request->filled('parent_ingredient_id') ? $request->integer('parent_ingredient_id') : null,
            $request->input('images', []),
            $request->input('complex_ingredient_part_ids', []),
            $prices,
        );
    }
}
