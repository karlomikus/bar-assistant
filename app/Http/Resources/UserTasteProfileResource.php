<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \BarAssistant\Application\Recommendation\DTO\UserTasteProfileDTO
 */
#[OAT\Schema(
    schema: 'UserTasteProfile',
    description: 'Represents a user taste profile with favorite and negative tags, and favorite ingredients',
    properties: [
        new OAT\Property(property: 'favorite_tags', type: 'array', items: new OAT\Items(type: 'object', required: ['name', 'weight'], properties: [
            new OAT\Property(property: 'name', type: 'string', example: 'Tropical'),
            new OAT\Property(property: 'weight', type: 'number', format: 'float', example: 1.0),
        ])),
        new OAT\Property(property: 'disliked_tags', type: 'array', items: new OAT\Items(type: 'object', required: ['name', 'weight'], properties: [
            new OAT\Property(property: 'name', type: 'string', example: 'Tropical'),
            new OAT\Property(property: 'weight', type: 'number', format: 'float', example: 1.0),
        ])),
        new OAT\Property(property: 'abv_distribution', type: 'array', items: new OAT\Items(type: 'object', required: ['bucket', 'count', 'ratio'], properties: [
            new OAT\Property(property: 'bucket', type: 'string', enum: ['low', 'medium', 'high'], example: 'medium', description: 'low: 0–10%, medium: >10–20%, high: >20%'),
            new OAT\Property(property: 'count', type: 'integer', example: 4),
            new OAT\Property(property: 'ratio', type: 'number', format: 'float', example: 0.5714),
        ])),
        new OAT\Property(property: 'average_abv', type: 'number', format: 'float', nullable: true, example: 18.5, description: 'Average ABV of preferred cocktails; null when no preference data exists'),
    ],
    required: ['favorite_tags', 'negative_tags', 'favorite_ingredients', 'average_abv', 'abv_distribution']
)]
class UserTasteProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'favorite_tags' => array_map(fn ($tag) => ['name' => $tag['name'], 'weight' => $tag['count']], $this->favoriteCocktailTags),
            'disliked_tags' => array_map(fn ($tag) => ['name' => $tag['name'], 'weight' => $tag['count']], $this->dislikedCocktailTags),
            'abv_distribution' => $this->abvDistribution,
            'average_abv' => $this->averageAbv,
        ];
    }
}
