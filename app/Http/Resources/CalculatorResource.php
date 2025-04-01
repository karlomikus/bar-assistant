<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Calculator
 */
#[OAT\Schema(
    schema: 'Calculator',
    description: 'Represents a calculator with basic information',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the calculator'),
        new OAT\Property(property: 'name', type: 'string', example: 'Calculator name', description: 'The name of the calculator'),
        new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Calculator description', description: 'The description of the calculator'),
        new OAT\Property(
            property: 'blocks',
            type: 'array',
            items: new OAT\Items(type: CalculatorBlockResource::class),
            description: 'The blocks of the calculator'
        ),
    ],
    required: ['id', 'name', 'blocks']
)]
class CalculatorResource extends JsonResource
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
            'blocks' => CalculatorBlockResource::collection($this->blocks),
        ];
    }
}
