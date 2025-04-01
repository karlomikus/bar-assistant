<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;
use Kami\Cocktail\OpenAPI\Schemas\CalculatorBlockSettings;

/**
 * @mixin \Kami\Cocktail\Models\CalculatorBlock
 */
#[OAT\Schema(
    schema: 'CalculatorBlock',
    description: 'Represents a calculator block with basic information',
    properties: [
        new OAT\Property(property: 'label', type: 'string', example: 'Block label', description: 'The label of the block'),
        new OAT\Property(property: 'variable_name', type: 'string', example: 'block_variable_name', description: 'The variable name of the block'),
        new OAT\Property(property: 'value', type: 'string', example: 'block_value', description: 'The value of the block'),
        new OAT\Property(property: 'sort', type: 'integer', example: 1, description: 'The sort order of the block'),
        new OAT\Property(property: 'type', type: 'string', example: 'number', description: 'The type of the block'),
        new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Block description', description: 'The description of the block'),
        new OAT\Property(property: 'settings', type: CalculatorBlockSettings::class, description: 'The settings of the block'),
    ],
    required: ['sort', 'label', 'variable_name', 'value', 'description', 'settings']
)]
class CalculatorBlockResource extends JsonResource
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
            'label' => $this->label,
            'variable_name' => $this->variable_name,
            'value' => $this->value,
            'sort' => $this->sort,
            'type' => $this->type->value,
            'description' => $this->description,
            'settings' => [
                'suffix' => $this->settings->suffix ?? null,
                'prefix' => $this->settings->prefix ?? null,
                'decimal_places' => $this->settings->decimal_places ?? null,
            ],
        ];
    }
}
