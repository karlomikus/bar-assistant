<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Export
 */
#[OAT\Schema(
    schema: 'Export',
    description: 'Export resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'filename', type: 'string', example: 'cocktails.csv'),
        new OAT\Property(property: 'created_at', format: 'date-time', type: 'string'),
        new OAT\Property(type: 'string', example: 'Bar name', property: 'bar_name'),
        new OAT\Property(type: 'boolean', example: true, property: 'is_done'),
    ],
)]
class ExportResource extends JsonResource
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
            'filename' => $this->filename,
            'created_at' => $this->created_at,
            'bar_name' => $this->bar->name ?? 'Unknown bar',
            'is_done' => (bool) $this->is_done,
        ];
    }
}
