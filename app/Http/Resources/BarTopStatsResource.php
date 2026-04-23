<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array
 */
#[OAT\Schema(
    schema: 'BarTopStatsResource',
    description: 'Resource representing total stats for a bar',
    properties: [
        new OAT\Property(property: 'top_bar_cocktails', type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'avg_rating', 'votes'], properties: [
            new OAT\Property(property: 'id', type: 'integer', example: 1),
            new OAT\Property(property: 'slug', type: 'string', example: 'gin'),
            new OAT\Property(property: 'name', type: 'string', example: 'Gin'),
            new OAT\Property(property: 'avg_rating', type: 'integer', example: 1),
            new OAT\Property(property: 'votes', type: 'integer', example: 1),
        ])),
        new OAT\Property(property: 'top_member_ingredients', type: 'array', items: new OAT\Items(type: 'object', required: ['id', 'slug', 'name', 'count'], properties: [
            new OAT\Property(property: 'id', type: 'integer', example: 1),
            new OAT\Property(property: 'slug', type: 'string', example: 'old-fashioned'),
            new OAT\Property(property: 'name', type: 'string', example: 'Old Fashioned'),
            new OAT\Property(property: 'count', type: 'integer', example: 3),
        ]))
    ],
    required: ['total_cocktails', 'total_ingredients', 'total_favorited_cocktails', 'total_shelf_cocktails', 'total_bar_shelf_ingredients', 'total_bar_shelf_cocktails', 'total_shelf_ingredients', 'total_bar_members', 'total_collections']
)]
class BarTopStatsResource extends JsonResource
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
