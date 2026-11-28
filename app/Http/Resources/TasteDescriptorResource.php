<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\TasteDescriptor
 */
#[OAT\Schema(
    schema: 'TasteDescriptor',
    description: 'Taste descriptor resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Taste descriptor ID'),
        new OAT\Property(property: 'name', type: 'string', example: 'Smoky', description: 'Taste descriptor display name'),
        new OAT\Property(property: 'group', type: 'string', example: 'family', enum: ['taste', 'family', 'flavor', 'mouthfeel', 'body', 'finish', 'profile'], description: 'Taxonomy group the descriptor belongs to'),
    ],
    required: ['id', 'name', 'group'],
)]
class TasteDescriptorResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'group' => $this->group,
        ];
    }
}
