<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Tag
 */
#[OAT\Schema(
    schema: 'Tag',
    description: 'Represents a tag with basic information',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the tag'),
        new OAT\Property(property: 'name', type: 'string', example: 'Floral', description: 'The name of the tag'),
        new OAT\Property(property: 'cocktails_count', type: 'integer', example: 12, description: 'The number of cocktails associated with the tag'),
    ],
    required: ['id', 'name', 'cocktails_count']
)]
class TagResource extends JsonResource
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
            'cocktails_count' => $this->whenCounted('cocktails'),
        ];
    }
}
