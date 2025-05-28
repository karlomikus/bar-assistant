<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Glass
 */
#[OAT\Schema(
    schema: 'Glass',
    description: 'Represents glassware',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the glassware'),
        new OAT\Property(property: 'name', type: 'string', example: 'Lowball', description: 'The name of the glassware'),
        new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Glass for smaller cocktails', description: 'The description of the glassware'),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 32, description: 'The number of cocktails that use this glassware'),
        new OAT\Property(property: 'volume', type: 'number', format: 'float', nullable: true, example: 120.0, description: 'The volume of the glassware'),
        new OAT\Property(property: 'volume_units', type: 'string', nullable: true, example: 'ml', description: 'The volume units of the glassware'),
        new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: ImageResource::class), description: 'Glassware images'),
    ],
    required: ['id', 'name', 'description', 'cocktails_count', 'volume', 'volume_units']
)]
class GlassResource extends JsonResource
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
            'volume' => $this->volume,
            'volume_units' => $this->volume_units,
            'cocktails_count' => $this->whenCounted('cocktails'),
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
        ];
    }
}
