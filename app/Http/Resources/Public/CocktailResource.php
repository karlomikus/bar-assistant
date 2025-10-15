<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources\Public;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\CocktailIngredient;
use Kami\Cocktail\Http\Resources\AmountFormats;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\Models\CocktailIngredientSubstitute;

/**
 * @mixin \Kami\Cocktail\Models\Cocktail
 */
#[OAT\Schema(
    schema: 'PublicCocktailResource',
    description: 'Public details about a cocktail',
    required: ['slug', 'name', 'instructions', 'garnish', 'description', 'source', 'public_id', 'public_at', 'images', 'tags', 'glass', 'utensils', 'method', 'created_at', 'abv', 'year', 'ingredients'],
    properties: [
       new OAT\Property(property: 'slug', type: 'string', example: 'cocktail-name-1', description: 'Unique string that can be used to reference a specific cocktail.'),
       new OAT\Property(property: 'name', type: 'string', example: 'Cocktail Name', description: 'Name of the cocktail'),
       new OAT\Property(property: 'instructions', type: 'string', example: 'Shake well and serve.', description: 'Instructions for preparing the cocktail'),
       new OAT\Property(property: 'garnish', type: 'string', nullable: true, example: 'Lemon twist', description: 'Garnish for the cocktail'),
       new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'A refreshing cocktail with a twist.', description: 'Description of the cocktail'),
       new OAT\Property(property: 'source', type: 'string', nullable: true, example: 'https://example.com/cocktail-recipe', description: 'Source of the cocktail recipe'),
       new OAT\Property(property: 'public_id', type: 'string', example: '12345', description: 'Public identifier (ULID) for the cocktail'),
       new OAT\Property(property: 'public_at', type: 'string', format: 'date-time', nullable: true, example: '2023-10-01T12:00:00Z', description: 'Date and time when the cocktail was made public'),
       new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: ImageResource::class), description: 'Images associated with the cocktail'),
       new OAT\Property(property: 'tags', type: 'array', items: new OAT\Items(type: 'string'), description: 'Tags associated with the cocktail'),
       new OAT\Property(property: 'glass', type: 'string', nullable: true, example: 'Highball glass', description: 'Type of glass used for the cocktail'),
       new OAT\Property(property: 'utensils', type: 'array', items: new OAT\Items(type: 'string'), description: 'Utensils used for preparing the cocktail'),
       new OAT\Property(property: 'method', type: 'string', nullable: true, example: 'Shaken', description: 'Method of preparation for the cocktail'),
       new OAT\Property(property: 'method_dilution_percentage', type: 'number', nullable: true, example: 12, description: 'Dilution percentage associated with the preparation method'),
       new OAT\Property(property: 'volume_ml', type: 'number', nullable: true, example: 120, description: 'Total volume of the cocktail in milliliters'),
       new OAT\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2023-10-01T12:00:00Z', description: 'Date and time when the cocktail was created'),
       new OAT\Property(property: 'in_bar_shelf', type: 'boolean', example: true, description: 'Indicates if the cocktail can be made in the current bar'),
       new OAT\Property(property: 'abv', type: 'number', format: 'float', nullable: true, example: 0.15, description: 'Alcohol by volume percentage of the cocktail'),
       new OAT\Property(property: 'year', type: 'integer', nullable: true, example: 2023, description: 'Year the cocktail was created or published'),
       new OAT\Property(
           property: 'ingredients',
           type: 'array',
           items: new OAT\Items(
               type: 'object',
               required: ['name', 'amount', 'amount_max', 'units', 'units_formatted', 'optional', 'note', 'substitutes'],
               properties: [
                   new OAT\Property(property: 'name', type: 'string', example: 'Gin', description: 'Name of the ingredient'),
                   new OAT\Property(property: 'amount', type: 'number', format: 'float', example: 50, description: 'Amount of the ingredient in the cocktail'),
                   new OAT\Property(property: 'amount_max', type: 'number', format: 'float', nullable: true, example: null, description: 'Maximum amount of the ingredient that can be used'),
                   new OAT\Property(property: 'units', type: 'string', example: 'ml', description: 'Units of measurement for the ingredient amount'),
                   new OAT\Property(property: 'units_formatted', type: AmountFormats::class, description: 'Formatted units for the ingredient amount'),
                   new OAT\Property(property: 'optional', type: 'boolean', example: false, description: 'Indicates if the ingredient is optional'),
                   new OAT\Property(property: 'note', type: 'string', nullable: true, example: 'Use fresh gin for best results.', description: 'Additional notes about the ingredient'),
                   new OAT\Property(
                       property: 'substitutes',
                       type: 'array',
                       description: 'List of substitute ingredients that can be used in place of this ingredient',
                       items: new OAT\Items(
                           type: 'object',
                           required: ['name', 'amount', 'amount_max', 'units'],
                           properties: [
                               new OAT\Property(property: 'name', type: 'string', example: 'Vodka', description: 'Name of the substitute ingredient'),
                               new OAT\Property(property: 'amount', type: 'number', format: 'float', example: 50, description: 'Amount of the substitute ingredient'),
                               new OAT\Property(property: 'amount_max', type: 'number', format: 'float', nullable: true, example: null, description: 'Maximum amount of the substitute ingredient that can be used'),
                               new OAT\Property(property: 'units', type: 'string', example: 'ml', description: 'Units of measurement for the substitute ingredient amount'),
                           ]
                       ),
                   ),
               ]
           ),
           description: 'List of ingredients required to make the cocktail'
       ),
    ],
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
            'slug' => $this->slug,
            'name' => $this->name,
            'instructions' => e($this->instructions),
            'garnish' => e($this->garnish),
            'description' => e($this->description),
            'source' => $this->source,
            'public_id' => $this->public_id,
            'public_at' => $this->public_at?->toAtomString() ?? null,
            'images' => ImageResource::collection($this->images),
            'tags' => $this->when(
                $this->relationLoaded('tags'),
                fn () => $this->tags->map(fn($tag) => $tag->name)
            ),
            'glass' => $this->glass->name ?? null,
            'utensils' => $this->utensils->pluck('name'),
            'method' => $this->method->name ?? null,
            'method_dilution_percentage' => $this->method->dilution_percentage ?? null,
            'volume_ml' => $this->getVolume(),
            'created_at' => $this->created_at->toAtomString(),
            'in_bar_shelf' => $this->inBarShelf(),
            'abv' => $this->abv,
            'year' => $this->year,
            'ingredients' => $this->ingredients->map(fn(CocktailIngredient $cocktailIngredient) => [
                'name' => $cocktailIngredient->ingredient->name,
                'amount' => $cocktailIngredient->amount,
                'amount_max' => $cocktailIngredient->amount_max,
                'units' => $cocktailIngredient->units,
                'units_formatted' => new AmountFormats($cocktailIngredient),
                'optional' => (bool) $cocktailIngredient->optional,
                'note' => $cocktailIngredient->note,
                'substitutes' => $cocktailIngredient->substitutes->map(fn(CocktailIngredientSubstitute $substitute) => [
                    'name' => $substitute->ingredient->name,
                    'amount' => $substitute->amount,
                    'amount_max' => $substitute->amount_max,
                    'units' => $substitute->units,
                ])->toArray(),
            ]),
        ];
    }
}
