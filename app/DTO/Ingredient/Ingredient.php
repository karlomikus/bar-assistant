<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Ingredient;

use Illuminate\Http\Request;

readonly class Ingredient
{
    /**
     * @param array<int> $images
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
        public array $images = []
    ) {
    }

    public static function fromIlluminateRequest(Request $request, int $barId): self
    {
        return new self(
            $barId,
            $request->post('name'),
            $request->user()->id,
            $request->post('ingredient_category_id') ? (int) $request->post('ingredient_category_id') : null,
            $request->float('strength'),
            $request->post('description'),
            $request->post('origin'),
            $request->post('color'),
            $request->post('parent_ingredient_id') ? (int) $request->post('parent_ingredient_id') : null,
            $request->post('images', []),
        );
    }
}
