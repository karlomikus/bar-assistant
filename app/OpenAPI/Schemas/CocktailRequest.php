<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'instructions'])]
readonly class CocktailRequest
{
    /**
     * @param array<string> $tags
     * @param array<CocktailIngredientRequest> $ingredients
     * @param array<int> $images
     * @param array<int> $utensils
     */
    public function __construct(
        #[OAT\Property(example: 'Cocktail name')]
        public string $name,
        #[OAT\Property(example: 'Step by step instructions')]
        public string $instructions,
        public int $userId,
        public int $barId,
        #[OAT\Property(example: 'Cocktail description')]
        public ?string $description = null,
        #[OAT\Property(example: 'Source of the recipe')]
        public ?string $source = null,
        #[OAT\Property(example: 'Garnish')]
        public ?string $garnish = null,
        #[OAT\Property(example: 1, property: 'glass_id')]
        public ?int $glassId = null,
        #[OAT\Property(example: 1, property: 'method_id')]
        public ?int $methodId = null,
        #[OAT\Property(items: new OAT\Items(type: 'string'))]
        public array $tags = [],
        #[OAT\Property(items: new OAT\Items(type: CocktailIngredientRequest::class))]
        public array $ingredients = [],
        #[OAT\Property(items: new OAT\Items(type: 'integer'), description: 'List of existing image ids')]
        public array $images = [],
        #[OAT\Property(items: new OAT\Items(type: 'integer'), description: 'List of existing utensil ids')]
        public array $utensils = [],
        #[OAT\Property(example: 1, property: 'parent_cocktail_id')]
        public ?int $parentCocktailId = null,
    ) {
    }

    public static function fromIlluminateRequest(Request $request, ?int $barId = null): self
    {
        /** @var array<mixed> */
        $formIngredients = $request->post('ingredients', []);

        $ingredients = [];
        foreach ($formIngredients as $formIngredient) {
            $ingredients[] = CocktailIngredientRequest::fromArray($formIngredient);
        }

        return new self(
            $request->input('name'),
            $request->input('instructions'),
            $request->user()->id,
            $barId ?? (int) bar()->id,
            $request->input('description'),
            $request->input('source'),
            $request->input('garnish'),
            $request->filled('glass_id') ? $request->integer('glass_id') : null,
            $request->filled('cocktail_method_id') ? $request->integer('cocktail_method_id') : null,
            $request->input('tags', []),
            $ingredients,
            $request->input('images', []),
            $request->input('utensils', []),
            $request->filled('parent_cocktail_id') ? $request->integer('parent_cocktail_id') : null,
        );
    }
}
