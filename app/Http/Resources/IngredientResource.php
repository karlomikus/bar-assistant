<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\OpenAPI\Schemas\IngredientHierarchy;

/**
 * @mixin \Kami\Cocktail\Models\Ingredient
 */
#[OAT\Schema(
    schema: 'Ingredient',
    description: 'Represents an ingredient',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the ingredient'),
        new OAT\Property(property: 'slug', type: 'string', example: 'vodka', description: 'The slug of the ingredient'),
        new OAT\Property(property: 'name', type: 'string', example: 'Vodka', description: 'The name of the ingredient'),
        new OAT\Property(property: 'strength', type: 'number', format: 'float', example: 40.0, description: 'The strength of the ingredient'),
        new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Vodka is a clear distilled alcoholic beverage', description: 'The description of the ingredient'),
        new OAT\Property(property: 'origin', type: 'string', nullable: true, example: 'Russia', description: 'The origin of the ingredient'),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2023-01-01T00:00:00Z', description: 'The creation date of the ingredient'),
        new OAT\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, example: '2023-01-01T00:00:00Z', description: 'The last update date of the ingredient'),
        new OAT\Property(property: 'materialized_path', type: 'string', nullable: true, example: '1.2.3', description: 'The materialized path of the ingredient'),
        new OAT\Property(property: 'hierarchy', type: IngredientHierarchy::class, description: 'The hierarchy of the ingredient'),
        new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: ImageResource::class), description: 'The images of the ingredient'),
        new OAT\Property(property: 'color', type: 'string', example: '#ffffff', description: 'The color of the ingredient', nullable: true),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 12, description: 'The number of cocktails that use this ingredient'),
        new OAT\Property(property: 'cocktails_as_substitute_count', type: 'integer', example: 1, description: 'Number of cocktails that use this ingredient as a substitute'),
        new OAT\Property(property: 'created_user', type: UserBasicResource::class, description: 'The user who created the ingredient'),
        new OAT\Property(property: 'updated_user', type: UserBasicResource::class, description: 'The user who created the ingredient', nullable: true),
        new OAT\Property(property: 'access', type: 'object', properties: [
            new OAT\Property(property: 'can_edit', type: 'boolean', example: true, description: 'Whether the user can edit the ingredient'),
            new OAT\Property(property: 'can_delete', type: 'boolean', example: false, description: 'Whether the user can delete the ingredient'),
        ], description: 'Access rights for the ingredient'),
        new OAT\Property(property: 'in_shelf', type: 'boolean', example: true, description: 'Whether the user has this ingredient in their shelf'),
        new OAT\Property(property: 'in_shelf_as_variant', type: 'boolean', example: true, description: 'Whether the user has this ingredient in their shelf as a variant'),
        new OAT\Property(property: 'in_bar_shelf', type: 'boolean', example: true, description: 'Whether the bar has this ingredient in their shelf'),
        new OAT\Property(property: 'in_bar_shelf_as_variant', type: 'boolean', example: true, description: 'Whether the bar has this ingredient in their shelf as a variant'),
        new OAT\Property(property: 'in_shopping_list', type: 'boolean', example: true, description: 'Whether the user has this ingredient in their shopping list'),
        new OAT\Property(property: 'used_as_substitute_for', type: 'array', items: new OAT\Items(type: IngredientBasicResource::class), description: 'Ingredients that this ingredient is used as a substitute for'),
        new OAT\Property(property: 'can_be_substituted_with', type: 'array', items: new OAT\Items(type: IngredientBasicResource::class), description: 'Ingredients that can be substituted with this ingredient'),
        new OAT\Property(property: 'ingredient_parts', type: 'array', items: new OAT\Items(type: IngredientBasicResource::class), description: 'Parts of this ingredient'),
        new OAT\Property(property: 'prices', type: 'array', items: new OAT\Items(type: IngredientPriceResource::class), description: 'Prices of the ingredient'),
        new OAT\Property(property: 'calculator_id', type: 'integer', example: 1, description: 'The calculator ID of the ingredient', nullable: true),
        new OAT\Property(property: 'sugar_g_per_ml', type: 'number', format: 'float', example: 0.0, description: 'The sugar content of the ingredient in grams per milliliter', nullable: true),
        new OAT\Property(property: 'acidity', type: 'number', format: 'float', example: 0.0, description: 'The acidity of the ingredient', nullable: true),
        new OAT\Property(property: 'distillery', type: 'string', example: 'Distillery Name', description: 'The distillery of the ingredient', nullable: true),
        new OAT\Property(property: 'units', type: 'string', example: 'ml', description: 'The units of the ingredient', nullable: true),
    ],
    required: ['id', 'slug', 'name', 'description', 'origin', 'color', 'created_at', 'updated_at', 'strength', 'hierarchy', 'materialized_path']
)]
class IngredientResource extends JsonResource
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
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'strength' => $this->strength,
            'description' => e($this->description),
            'origin' => $this->origin,
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'materialized_path' => $this->materialized_path,
            'hierarchy' => [
                'root_ingredient_id' => $this->getMaterializedPath()->toArray()[0] ?? null,
                'path_to_self' => $this->when($this->relationLoaded('ancestors') && $this->parent_ingredient_id, fn () => $this->ancestors->pluck('name')->implode(' > ')),
                'parent_ingredient' => $this->whenLoaded('parentIngredient', fn () => new IngredientBasicResource($this->parentIngredient)),
                'descendants' => IngredientBasicResource::collection($this->whenLoaded('descendants')),
                'ancestors' => IngredientBasicResource::collection($this->whenLoaded('ancestors')),
            ],
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
            'color' => $this->color,
            'cocktails_count' => $this->whenCounted('cocktails'),
            'cocktails_as_substitute_count' => $this->when(
                $this->relationLoaded('cocktailIngredientSubstitutes'),
                fn () => $this->cocktailsAsSubstituteIngredient()->count()
            ),
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'access' => $this->when(true, fn () => [
                'can_edit' => $request->user()->can('edit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
            ]),
            'in_shelf' => $this->userHasInShelf($request->user()),
            'in_shelf_as_variant' => $this->when($this->relationLoaded('descendants'), fn () => $this->userShelfVariants($request->user())->count() > 0),
            'in_bar_shelf' => $this->barHasInShelf(),
            'in_bar_shelf_as_variant' => $this->when($this->relationLoaded('descendants'), fn () => $this->barShelfVariants()->count() > 0),
            'in_shopping_list' => $this->userHasInShoppingList($request->user()),
            'used_as_substitute_for' => $this->when(
                $this->relationLoaded('cocktailIngredientSubstitutes'),
                fn () => IngredientBasicResource::collection($this->getIngredientsUsedAsSubstituteFor())
            ),
            'can_be_substituted_with' => $this->when(
                $this->relationLoaded('cocktailIngredientSubstitutes'),
                fn () => IngredientBasicResource::collection($this->getCanBeSubstitutedWithIngredients())
            ),
            'ingredient_parts' => $this->when(
                $this->relationLoaded('ingredientParts'),
                fn () => $this->ingredientParts->map(fn ($cip) => new IngredientBasicResource($cip->ingredient))
            ),
            'prices' => $this->when($this->relationLoaded('prices'), function () {
                return IngredientPriceResource::collection($this->prices);
            }),
            'calculator_id' => $this->calculator_id,
            'sugar_g_per_ml' => $this->sugar_g_per_ml,
            'acidity' => $this->acidity,
            'distillery' => $this->distillery,
            'units' => $this->units,
        ];
    }
}
