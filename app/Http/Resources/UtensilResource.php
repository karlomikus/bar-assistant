<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Utensil
 */
#[OAT\Schema(
    schema: 'Utensil',
    description: 'Represents a utensil with basic information',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the utensil'),
        new OAT\Property(property: 'name', type: 'string', example: 'Shaker', description: 'The name of the utensil'),
        new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Used to shake ingredients', description: 'The description of the utensil'),
    ],
    required: ['id', 'name', 'description']
)]
class UtensilResource extends JsonResource
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
            'description' => $this->description,
        ];
    }
}
