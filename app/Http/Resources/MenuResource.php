<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Menu
 */
#[OAT\Schema(
    schema: 'Menu',
    description: 'Menu resource',
    properties: [
        new OAT\Property(property: 'id', example: 1, type: 'integer', description: 'Menu ID'),
        new OAT\Property(property: 'is_enabled', type: 'boolean', example: true, description: 'Is menu enabled'),
        new OAT\Property(property: 'created_at', format: 'date-time', type: 'string', description: 'Creation date'),
        new OAT\Property(property: 'updated_at', format: 'date-time', type: 'string', description: 'Last update date', nullable: true),
        new OAT\Property(property: 'categories', type: 'array', items: new OAT\Items(type: MenuCategoryResource::class), description: 'Menu categories'),
    ],
    required: ['id', 'is_enabled', 'created_at', 'updated_at', 'categories']
)]
class MenuResource extends JsonResource
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
            'is_enabled' => (bool) $this->is_enabled,
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'categories' => MenuCategoryResource::collection($this->categories),
        ];
    }
}
