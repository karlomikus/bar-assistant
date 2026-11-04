<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

#[OAT\Schema(
    schema: 'ShelfPhotoResultResource',
    description: 'Result of identifying bottles on an uploaded shelf photo',
    properties: [
        new OAT\Property(property: 'stocked', type: 'array', description: 'Bar ingredients marked as in stock', items: new OAT\Items(type: IngredientBasicResource::class)),
        new OAT\Property(property: 'unmatched', type: 'array', description: 'Bottle names read from the photo that match no bar ingredient and were not stocked', items: new OAT\Items(type: 'string', example: 'Captin Morgon')),
    ],
    required: ['stocked', 'unmatched'],
)]
class ShelfPhotoResultResource extends JsonResource
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
            'stocked' => $this->resource['stocked'],
            'unmatched' => $this->resource['unmatched'],
        ];
    }
}
