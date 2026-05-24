<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

#[OAT\Schema(
    schema: 'BarTotalStatsResource',
    description: 'Resource representing total stats for a bar',
    properties: [
        new OAT\Property(property: 'total_cocktails', type: 'int', example: 1),
        new OAT\Property(property: 'total_ingredients', type: 'int', example: 1),
        new OAT\Property(property: 'total_favorited_cocktails', type: 'int', example: 1),
        new OAT\Property(property: 'total_shelf_cocktails', type: 'int', example: 1),
        new OAT\Property(property: 'total_bar_shelf_ingredients', type: 'int', example: 1),
        new OAT\Property(property: 'total_bar_shelf_cocktails', type: 'int', example: 1),
        new OAT\Property(property: 'total_collections', type: 'int', example: 1),
        new OAT\Property(property: 'total_bar_members', type: 'int', example: 1),
    ],
    required: ['total_cocktails', 'total_ingredients', 'total_favorited_cocktails', 'total_shelf_cocktails', 'total_bar_shelf_ingredients', 'total_bar_shelf_cocktails', 'total_bar_members', 'total_collections']
)]
class BarTotalStatsResource extends JsonResource
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
