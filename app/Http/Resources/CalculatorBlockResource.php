<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\CalculatorBlock
 */
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
