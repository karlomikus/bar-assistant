<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Note
 */
#[OAT\Schema(
    schema: 'Note',
    description: 'Note resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Note ID'),
        new OAT\Property(property: 'note', type: 'string', example: 'Note text', description: 'Note text'),
        new OAT\Property(property: 'user_id', type: 'integer', example: 1, description: 'User ID'),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2022-01-01T00:00:00+00:00', description: 'Creation date and time'),
    ],
    required: [
        'id',
        'note',
        'user_id',
        'created_at',
    ],
)]
class NoteResource extends JsonResource
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
            'note' => $this->note,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at->toAtomString(),
        ];
    }
}
