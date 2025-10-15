<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources\Public;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Bar
 */
#[OAT\Schema(
    schema: 'PublicBarResource',
    description: 'Public details about a bar',
    properties: [
       new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Unique number that can be used to reference a specific bar.'),
       new OAT\Property(property: 'slug', type: 'string', example: 'bar-name-1', description: 'Unique string that can be used to reference a specific bar.'),
       new OAT\Property(property: 'name', type: 'string', example: 'Bar name', description: 'Name of the bar'),
       new OAT\Property(property: 'subtitle', type: 'string', nullable: true, example: 'A short subtitle of a bar', description: 'Optional short quip about the bar'),
       new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Bar description', description: 'Description of the bar'),
       new OAT\Property(property: 'images', type: 'array', items: new OAT\Items(type: ImageResource::class), description: 'Images associated with the bar'),
       new OAT\Property(property: 'is_menu_enabled', type: 'boolean', example: true, description: 'Whether the bar has enabled its menu for public viewing'),
    ],
    required: ['id', 'slug', 'name', 'subtitle', 'description', 'images', 'is_menu_enabled'],
)]
class BarResource extends JsonResource
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
            'description' => $this->description,
            'images' => ImageResource::collection($this->images),
            'is_menu_enabled' => $this->menu->is_enabled ?? false,
        ];
    }
}
