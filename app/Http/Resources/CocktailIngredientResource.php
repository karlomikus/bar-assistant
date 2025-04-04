<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CocktailIngredient
 */
#[OAT\Schema(
    schema: 'CocktailIngredient',
    description: 'Cocktail ingredient',
    properties: [
        new OAT\Property(property: 'sort', type: 'integer', example: 1, description: 'Sort order of the ingredient'),
        new OAT\Property(property: 'amount', type: 'number', example: 30, format: 'float', description: 'Amount of the ingredient'),
        new OAT\Property(property: 'amount_max', type: 'number', example: 60, format: 'float', description: 'Amount of the ingredient', nullable: true),
        new OAT\Property(property: 'units', type: 'string', example: 'ml', description: 'Units of the ingredient'),
        new OAT\Property(property: 'optional', type: 'boolean', example: false, description: 'Is the ingredient optional'),
        new OAT\Property(property: 'ingredient', type: IngredientBasicResource::class, description: 'Ingredient information'),
        new OAT\Property(property: 'substitutes', type: 'array', items: new OAT\Items(type: CocktailIngredientSubstituteResource::class), description: 'Substitutes for the ingredient'),
        new OAT\Property(property: 'variants_in_shelf', type: 'array', items: new OAT\Items(type: IngredientBasicResource::class), description: 'Variants of the ingredient in the shelf'),
        new OAT\Property(property: 'note', type: 'string', example: 'Additional notes', description: 'Additional notes about the ingredient', nullable: true),
        new OAT\Property(property: 'is_specified', type: 'boolean', example: false, description: 'Is the ingredient specified (ignores variants in matching)'),
        new OAT\Property(property: 'formatted', type: AmountFormats::class),
        new OAT\Property(property: 'in_shelf', type: 'boolean', example: true, description: 'Is the ingredient in the user\'s shelf'),
        new OAT\Property(property: 'in_shelf_as_variant', type: 'boolean', example: true, description: 'Is the ingredient in the user\'s shelf as a variant'),
        new OAT\Property(property: 'in_shelf_as_substitute', type: 'boolean', example: true, description: 'Is the ingredient in the user\'s shelf as a substitute'),
        new OAT\Property(property: 'in_shelf_as_complex_ingredient', type: 'boolean', example: true, description: 'Is the ingredient in the user\'s shelf as a complex ingredient'),
        new OAT\Property(property: 'in_bar_shelf', type: 'boolean', example: true, description: 'Is the ingredient in the bar shelf'),
        new OAT\Property(property: 'in_bar_shelf_as_substitute', type: 'boolean', example: true, description: 'Is the ingredient in the bar shelf as a substitute'),
        new OAT\Property(property: 'in_bar_shelf_as_complex_ingredient', type: 'boolean', example: true, description: 'Is the ingredient in the bar shelf as a complex ingredient'),
        new OAT\Property(property: 'in_bar_shelf_as_variant', type: 'boolean', example: true, description: 'Is the ingredient in the bar shelf as a variant'),
    ],
    required: ['ingredient', 'sort', 'amount', 'units', 'formatted', 'is_specified', 'note']
)]
class CocktailIngredientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'sort' => $this->sort,
            'amount' => $this->amount,
            'amount_max' => $this->amount_max,
            'units' => $this->units,
            'optional' => (bool) $this->optional,
            'ingredient' => new IngredientBasicResource($this->ingredient),
            'substitutes' => CocktailIngredientSubstituteResource::collection($this->whenLoaded('substitutes')),
            'variants_in_shelf' => $this->when($this->ingredient->relationLoaded('descendants'), fn () => IngredientBasicResource::collection($this->ingredient->barShelfVariants())),
            'note' => $this->note,
            'is_specified' => (bool) $this->is_specified,
            'formatted' => new AmountFormats($this->resource),
            'in_shelf' => $this->when(
                $this->relationLoaded('ingredient'),
                fn () => $this->ingredient->userHasInShelf($request->user())
            ),
            'in_shelf_as_variant' => $this->when(
                $this->ingredient->relationLoaded('descendants'),
                fn () => !$this->is_specified && $this->ingredient->userShelfVariants($request->user())->count() > 0
            ),
            'in_shelf_as_substitute' => $this->when(
                $this->relationLoaded('substitutes'),
                fn () => $this->userHasInShelfAsSubstitute($request->user())
            ),
            'in_shelf_as_complex_ingredient' => $this->when(
                $this->ingredient->relationLoaded('ingredientParts'),
                fn () => $this->ingredient->userHasInShelfAsComplexIngredient($request->user())
            ),
            'in_bar_shelf' => $this->when(
                $this->relationLoaded('ingredient'),
                fn () => $this->ingredient->barHasInShelf()
            ),
            'in_bar_shelf_as_substitute' => $this->when(
                $this->relationLoaded('substitutes'),
                fn () => $this->barHasInShelfAsSubstitute()
            ),
            'in_bar_shelf_as_complex_ingredient' => $this->when(
                $this->ingredient->relationLoaded('ingredientParts'),
                fn () => $this->ingredient->barHasInShelfAsComplexIngredient()
            ),
            'in_bar_shelf_as_variant' => $this->when(
                $this->ingredient->relationLoaded('descendants'),
                fn () => !$this->is_specified && $this->ingredient->barShelfVariants()->count() > 0
            ),
        ];
    }
}
