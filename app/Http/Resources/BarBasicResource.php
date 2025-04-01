<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Bar
 */
#[OAT\Schema(
    schema: 'BarBasic',
    description: 'Represents a bar with basic information',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the bar'),
        new OAT\Property(property: 'slug', type: 'string', example: 'bar-name-1', description: 'The slug of the bar'),
        new OAT\Property(property: 'name', type: 'string', example: 'Bar name', description: 'The name of the bar'),
        new OAT\Property(property: 'subtitle', type: 'string', nullable: true, example: 'Bar subtitle', description: 'The subtitle of the bar'),
    ],
    required: ['id', 'slug', 'name', 'subtitle']
)]
class BarBasicResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
        ];
    }
}
