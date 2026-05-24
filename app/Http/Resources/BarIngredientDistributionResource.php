<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

#[OAT\Schema(
    schema: 'BarIngredientDistributionResource',
    description: 'Resource representing total stats for a bar',
    properties: [
        new OAT\Property(property: 'main_category_ingredient_distribution', type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'ingredients_count'], properties: [
            new OAT\Property(property: 'id', type: 'integer', example: 1),
            new OAT\Property(property: 'slug', type: 'string', example: 'spirits'),
            new OAT\Property(property: 'name', type: 'string', example: 'Spirits'),
            new OAT\Property(property: 'ingredients_count', type: 'integer', example: 12),
        ])),
    ],
    required: ['main_category_ingredient_distribution']
)]
class BarIngredientDistributionResource extends JsonResource
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
        return $this->resource;
    }
}
