<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\PriceCategory
 */
#[OAT\Schema(
    schema: 'PriceCategory',
    description: 'Price category',
    properties: [
        new OAT\Property(type: 'integer', example: 1, property: 'id'),
        new OAT\Property(type: 'string', example: 'Amazon (DE)', property: 'name'),
        new OAT\Property(type: 'string', nullable: true, example: 'Current price on amazon.de', property: 'description'),
        new OAT\Property(type: 'string', example: 'EUR', format: 'ISO 4217', property: 'currency'),
        new OAT\Property(type: 'string', example: '€', property: 'currency_symbol'),
    ],
    required: ['id', 'name', 'description', 'currency']
)]
class PriceCategoryResource extends JsonResource
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
            'currency' => $this->currency,
            'currency_symbol' => '',
        ];
    }
}
