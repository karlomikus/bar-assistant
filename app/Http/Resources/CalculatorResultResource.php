<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\ValueObjects\CalculatorResult
 */
#[OAT\Schema(
    schema: 'CalculatorResult',
    description: 'Represents the result of a calculator',
    properties: [
        new OAT\Property(property: 'inputs', type: 'object', additionalProperties: new OAT\AdditionalProperties(type: 'string'), description: 'The inputs of the calculator'),
        new OAT\Property(property: 'results', type: 'object', additionalProperties: new OAT\AdditionalProperties(type: 'string'), description: 'The results of the calculator'),
    ],
    required: ['inputs', 'results']
)]
class CalculatorResultResource extends JsonResource
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
            'inputs' => array_map('strval', $this->inputs),
            'results' => array_map('strval', $this->results),
        ];
    }
}
