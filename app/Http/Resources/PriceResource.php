<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\ValueObjects\Price
 */
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
