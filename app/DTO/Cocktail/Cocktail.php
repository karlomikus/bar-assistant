<?php

declare(strict_types=1);

namespace Kami\Cocktail\DTO\Cocktail;

use Illuminate\Http\Request;

readonly class Cocktail
{
    /**
     * @param array<?string> $tags
     * @param array<Ingredient> $ingredients
     * @param array<int> $images
     * @param array<int> $utensils
     */
    public function __construct(
        public string $name,
        public string $instructions,
        public int $userId,
        public int $barId,
        public ?string $description = null,
        public ?string $source = null,
        public ?string $garnish = null,
        public ?int $glassId = null,
        public ?int $methodId = null,
        public array $tags = [],
        public array $ingredients = [],
        public array $images = [],
        public array $utensils = [],
    ) {
    }

    public static function fromIlluminateRequest(Request $request, int $barId): self
    {
        $ingredients = [];
        foreach ($request->post('ingredients', []) as $formIngredient) {
            $ingredients[] = Ingredient::fromArray($formIngredient);
        }

        return new self(
            $request->post('name'),
            $request->post('instructions'),
            $request->user()->id,
            $barId,
            $request->post('description'),
            $request->post('source'),
            $request->post('garnish'),
            $request->post('glass_id') ? (int) $request->post('glass_id') : null,
            $request->post('cocktail_method_id') ? (int) $request->post('cocktail_method_id') : null,
            $request->post('tags', []),
            $ingredients,
            $request->post('images', []),
            $request->post('utensils', []),
        );
    }
}
