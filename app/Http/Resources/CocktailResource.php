<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
#[OAT\Schema(
    schema: 'Cocktail',
    description: 'Cocktail resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Cocktail ID'),
        new OAT\Property(property: 'name', type: 'string', example: 'Cocktail name', description: 'Cocktail name'),
        new OAT\Property(property: 'slug', type: 'string', example: 'cocktail-name-1', description: 'Cocktail slug'),
        new OAT\Property(property: 'instructions', type: 'string', example: 'Step by step instructions', description: 'Cocktail instructions'),
        new OAT\Property(property: 'garnish', type: 'string', example: 'Garnish', description: 'Cocktail garnish', nullable: true),
        new OAT\Property(property: 'description', type: 'string', example: 'Cocktail description', description: 'Cocktail description', nullable: true),
        new OAT\Property(property: 'source', type: 'string', example: 'Source of the recipe', description: 'Cocktail source', nullable: true),
        new OAT\Property(property: 'public_id', type: 'string', example: 'public-id-1', description: 'Public ID of the cocktail', nullable: true),
        new OAT\Property(property: 'public_at', type: 'string', format: 'date-time', example: '2023-10-01T12:00:00Z', description: 'Public date of the cocktail', nullable: true),
        new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: ImageResource::class), description: 'Cocktail images'),
        new OAT\Property(property: 'tags', type: 'array', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Tag ID'),
            new OAT\Property(property: 'name', type: 'string', example: 'Tag name', description: 'Tag name'),
        ], required: ['id', 'name']), description: 'Cocktail tags'),
        new OAT\Property(property: 'rating', type: 'object', required: ['user', 'average', 'total_votes'], properties: [
            new OAT\Property(type: 'integer', property: 'user', example: 1, nullable: true, description: 'Current user\'s rating'),
            new OAT\Property(type: 'integer', property: 'average', example: 4, description: 'Average rating'),
            new OAT\Property(type: 'integer', property: 'total_votes', example: 12),
        ]),
        new OAT\Property(property: 'glass', type: GlassResource::class, description: 'Cocktail glass', nullable: true),
        new OAT\Property(property: 'utensils', type: 'array', items: new OAT\Items(type: UtensilResource::class), description: 'Cocktail utensils'),
        new OAT\Property(property: 'ingredients', type: 'array', items: new OAT\Items(type: CocktailIngredientResource::class), description: 'Cocktail ingredients'),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2023-10-01T12:00:00Z', description: 'Creation date of the cocktail'),
        new OAT\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2023-10-01T12:00:00Z', description: 'Last update date of the cocktail', nullable: true),
        new OAT\Property(property: 'method', type: CocktailMethodResource::class, description: 'Cocktail method', nullable: true),
        new OAT\Property(property: 'abv', type: 'number', format: 'float', example: 0.5, description: 'Alcohol by volume (ABV) percentage', nullable: true),
        new OAT\Property(property: 'volume_ml', type: 'number', format: 'float', example: 200, description: 'Cocktail volume in milliliters'),
        new OAT\Property(property: 'alcohol_units', type: 'number', format: 'float', example: 1.5, description: 'Alcohol units in the cocktail'),
        new OAT\Property(property: 'calories', type: 'number', format: 'float', example: 150, description: 'Calories in the cocktail'),
        new OAT\Property(property: 'created_user', type: UserBasicResource::class, description: 'User who created the cocktail'),
        new OAT\Property(property: 'updated_user', type: UserBasicResource::class, description: 'User who last updated the cocktail', nullable: true),
        new OAT\Property(property: 'in_shelf', type: 'boolean', example: true, description: 'Is the cocktail in the user\'s shelf'),
        new OAT\Property(property: 'in_bar_shelf', type: 'boolean', example: true, description: 'Is the cocktail in the bar\'s shelf'),
        new OAT\Property(property: 'is_favorited', type: 'boolean', example: true, description: 'Is the cocktail favorited by the user'),
        new OAT\Property(property: 'access', type: 'object', description: 'User access to the cocktail', properties: [
            new OAT\Property(property: 'can_edit', type: 'boolean', example: true, description: 'Can the user edit the cocktail'),
            new OAT\Property(property: 'can_delete', type: 'boolean', example: true, description: 'Can the user delete the cocktail'),
            new OAT\Property(property: 'can_rate', type: 'boolean', example: true, description: 'Can the user rate the cocktail'),
            new OAT\Property(property: 'can_add_note', type: 'boolean', example: true, description: 'Can the user add a note to the cocktail'),
        ], required: [
            'can_edit',
            'can_delete',
            'can_rate',
            'can_add_note',
        ]),
        new OAT\Property(property: 'parent_cocktail', type: CocktailBasicResource::class, description: 'If this cocktail is a variety of existing cocktail, this will reference the original cocktail', nullable: true),
        new OAT\Property(property: 'varieties', type: 'array', items: new OAT\Items(type: CocktailBasicResource::class), description: 'List of varieties of this cocktail'),
        new OAT\Property(property: 'year', type: 'number', example: 2023, description: 'Cocktail recipe year', nullable: true),
    ],
    required: [
        'id',
        'name',
        'slug',
        'garnish',
        'description',
        'instructions',
        'source',
        'public_id',
        'public_at',
        'created_at',
        'updated_at',
        'abv',
    ]
)]
class CocktailResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'public_id' => $this->public_id,
            'public_at' => $this->public_at?->toAtomString() ?? null,
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
            'tags' => $this->when(
                $this->relationLoaded('tags'),
                fn () => $this->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])
            ),
            'rating' => $this->when(
                $this->relationLoaded('ratings'),
                fn () => [
                    'user' => $this->user_rating ?? null,
                    'average' => (int) round($this->average_rating ?? 0),
                    'total_votes' => $this->totalRatedCount(),
                ]
            ),
            'glass' => new GlassResource($this->whenLoaded('glass')),
            'utensils' => UtensilResource::collection($this->whenLoaded('utensils')),
            'ingredients' => CocktailIngredientResource::collection($this->whenLoaded('ingredients')),
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'method' => new CocktailMethodResource($this->whenLoaded('method')),
            'abv' => $this->abv,
            'volume_ml' => $this->when($this->relationLoaded('ingredients'), fn () => $this->getVolume()),
            'alcohol_units' => $this->when($this->relationLoaded('method'), fn () => $this->getAlcoholUnits()),
            'calories' => $this->when($this->relationLoaded('method'), fn () => $this->getCalories()),
            'created_user' => new UserBasicResource($this->whenLoaded('createdUser')),
            'updated_user' => new UserBasicResource($this->whenLoaded('updatedUser')),
            'in_shelf' => in_array($this->id, $request->user()->getShelfCocktailsOnce($this->bar_id)),
            'in_bar_shelf' => in_array($this->id, $this->bar->getShelfCocktailsOnce()),
            'is_favorited' => $request->user()->getBarMembership($this->bar_id)->cocktailFavorites->where('cocktail_id', $this->id)->isNotEmpty(),
            'access' => $this->when(true, fn () => [
                'can_edit' => $request->user()->can('edit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
                'can_rate' => $request->user()->can('rate', $this->resource),
                'can_add_note' => $request->user()->can('addNote', $this->resource),
            ]),
            'parent_cocktail' => $this->whenLoaded('parentCocktail', fn () => new CocktailBasicResource($this->parentCocktail)),
            'varieties' => CocktailBasicResource::collection($this->whenLoaded('cocktailVarieties')),
            'year' => $this->year,
        ];
    }
}
