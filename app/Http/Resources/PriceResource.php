<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\ValueObjects\Price
 */
#[OAT\Schema(
    schema: 'Price',
    description: 'Schema representing a price',
    properties: [
        new OAT\Property(property: 'price', type: 'number', example: 13.39, description: 'Price in major units (e.g., euros)'),
        new OAT\Property(property: 'price_minor', type: 'integer', example: 1339, description: 'Price in minor units (e.g., cents)'),
        new OAT\Property(property: 'formatted_price', type: 'string', example: 'EUR 13.39', description: 'Pretty formatted price string'),
        new OAT\Property(property: 'currency', type: 'string', example: 'EUR', description: 'Currency code in ISO 4217 format'),
    ],
    required: ['price', 'price_minor', 'formatted_price', 'currency']
)]
class PriceResource extends JsonResource
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
            'price' => $this->getPriceAsFloat(),
            'price_minor' => $this->getPriceAsMinor(),
            'formatted_price' => $this->getFormattedPrice(),
            'currency' => $this->getCurrency(),
        ];
    }
}
