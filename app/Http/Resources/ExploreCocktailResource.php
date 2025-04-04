<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Image;
use Kami\Cocktail\Models\CocktailIngredient;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
#[OAT\Schema(
    schema: 'CocktailExplore',
    description: 'Cocktail explore resource',
    properties: [
        new OAT\Property(property: 'bar', type: BarBasicResource::class),
        new OAT\Property(property: 'name', type: 'string', example: 'Cocktail name'),
        new OAT\Property(property: 'instructions', type: 'string', example: 'Step by step instructions'),
        new OAT\Property(property: 'garnish', type: 'string', example: 'Garnish', nullable: true),
        new OAT\Property(property: 'description', type: 'string', example: 'Cocktail description', nullable: true),
        new OAT\Property(property: 'source', type: 'string', example: 'Source of the recipe', nullable: true),
        new OAT\Property(property: 'tags', type: 'array', items: new OAT\Items(type: 'string')),
        new OAT\Property(property: 'glass', type: 'string', nullable: true),
        new OAT\Property(property: 'utensils', type: 'array', items: new OAT\Items(type: 'string')),
        new OAT\Property(property: 'method', type: 'string', nullable: true),
        new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(property: 'sort', type: 'integer', example: 1),
            new OAT\Property(property: 'placeholder_hash', type: 'string', example: 'a1b2c3d4e5f6g7h8i9j0'),
            new OAT\Property(property: 'url', type: 'string', example: 'https://example.com/image.jpg'),
            new OAT\Property(property: 'copyright', type: 'string', example: 'Image copyright'),
        ])),
        new OAT\Property(property: 'ingredients', type: 'array', items: new OAT\Items(type: 'object', properties: [
            new OAT\Property(property: 'ingredient', type: 'object', properties: [
                new OAT\Property(property: 'name', type: 'string', example: 'Ingredient name'),
            ]),
            new OAT\Property(property: 'amount', type: 'number', example: 30.0),
            new OAT\Property(property: 'amount_max', type: 'number', example: 45.0, nullable: true),
            new OAT\Property(property: 'units', type: 'string', example: 'ml'),
            new OAT\Property(property: 'optional', type: 'boolean', example: true),
            new OAT\Property(property: 'note', type: 'string', example: 'Ingredient note', nullable: true),
            new OAT\Property(property: 'substitutes', type: 'array', items: new OAT\Items(type: 'object', properties: [
                new OAT\Property(property: 'ingredient', type: 'object', properties: [
                    new OAT\Property(property: 'name', type: 'string', example: 'Ingredient name'),
                ]),
                new OAT\Property(property: 'amount', type: 'number', example: 30.0, nullable: true),
                new OAT\Property(property: 'amount_max', type: 'number', example: 45.0, nullable: true),
                new OAT\Property(property: 'units', type: 'string', example: 'ml', nullable: true),
            ])),
        ])),
        new OAT\Property(property: 'abv', type: 'number', format: 'float', example: 0.5, description: 'Alcohol by volume (ABV) percentage', nullable: true),
    ],
    required: [
        'bar',
        'name',
        'instructions',
        'garnish',
        'description',
        'source',
        'tags',
        'glass',
        'utensils',
        'method',
        'images',
        'ingredients',
    ]
)]
class ExploreCocktailResource extends JsonResource
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
            'bar' => new BarBasicResource($this->bar),
            'name' => $this->name,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'tags' => $this->tags->pluck('name'),
            'glass' => $this->glass->name ?? null,
            'utensils' => $this->utensils->pluck('name'),
            'method' => $this->method->name ?? null,
            'images' => $this->images->map(function (Image $image) {
                return [
                    'sort' => $image->sort,
                    'placeholder_hash' => $image->placeholder_hash,
                    'url' => $image->getImageUrl(),
                    'copyright' => $image->copyright,
                ];
            }),
            'abv' => $this->abv,
            'ingredients' => $this->ingredients->map(function (CocktailIngredient $cocktailIngredient) {
                return [
                    'ingredient' => [
                        'name' => $cocktailIngredient->ingredient->name,
                    ],
                    'amount' => $cocktailIngredient->amount,
                    'amount_max' => $cocktailIngredient->amount_max,
                    'units' => $cocktailIngredient->units,
                    'optional' => (bool) $cocktailIngredient->optional,
                    'note' => $cocktailIngredient->note,
                    'substitutes' => $cocktailIngredient->substitutes->map(function (CocktailIngredientSubstitute $substitute) {
                        return [
                            'ingredient' => [
                                'name' => $substitute->ingredient->name,
                            ],
                            'amount' => $substitute->amount,
                            'amount_max' => $substitute->amount_max,
                            'units' => $substitute->units,
                        ];
                    })->toArray(),
                ];
            }),
        ];
    }
}
