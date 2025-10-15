<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Collection
 */
#[OAT\Schema(
    schema: 'Collection',
    description: 'Collection resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'name', type: 'string', example: 'Collection name'),
        new OAT\Property(property: 'description', type: 'string', nullable: true, example: 'Collection description'),
        new OAT\Property(property: 'is_bar_shared', type: 'boolean'),
        new OAT\Property(property: 'created_at', format: 'date-time', example: '2023-05-14T21:23:40.000000Z'),
        new OAT\Property(property: 'created_user', type: UserBasicResource::class),
        new OAT\Property(property: 'cocktails', type: 'array', items: new OAT\Items(type: CocktailBasicResource::class)),
    ],
    required: ['id', 'name', 'description', 'is_bar_shared', 'created_at']
)]
class CollectionResource extends JsonResource
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
            'is_bar_shared' => $this->is_bar_shared,
            'created_at' => $this->created_at->toAtomString(),
            'created_user' => $this->whenLoaded('barMembership', fn() => new UserBasicResource($this->barMembership->user)),
            'cocktails' => CocktailBasicResource::collection($this->whenLoaded('cocktails')),
        ];
    }
}
