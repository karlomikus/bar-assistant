<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CocktailMethod
 */
#[OAT\Schema(
    schema: 'CocktailMethod',
    description: 'Cocktail method resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Cocktail method ID'),
        new OAT\Property(property: 'name', type: 'string', example: 'Shake', description: 'Cocktail method name'),
        new OAT\Property(property: 'dilution_percentage', type: 'integer', example: 20, description: 'Dilution percentage'),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 32, description: 'Number of cocktails using this method'),
    ],
    required: ['id', 'name', 'dilution_percentage'],
)]
class CocktailMethodResource extends JsonResource
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
            'dilution_percentage' => $this->dilution_percentage,
            'cocktails_count' => $this->whenCounted('cocktails'),
        ];
    }
}
