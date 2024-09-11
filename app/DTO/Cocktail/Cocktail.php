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
        $formIngredients = $request->post('ingredients', []);

        $ingredients = [];
        if (is_array($formIngredients)) {
            foreach ($formIngredients as $formIngredient) {
                $ingredients[] = Ingredient::fromArray($formIngredient);
            }
        }

        return new self(
            $request->input('name'),
            $request->input('instructions'),
            $request->user()->id,
            $barId,
            $request->input('description'),
            $request->input('source'),
            $request->input('garnish'),
            $request->filled('glass_id') ? $request->integer('glass_id') : null,
            $request->filled('cocktail_method_id') ? $request->integer('cocktail_method_id') : null,
            $request->input('tags', []),
            $ingredients,
            $request->input('images', []),
            $request->input('utensils', []),
        );
    }
}
