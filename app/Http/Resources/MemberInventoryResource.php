<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\MemberInventory
 */
#[OAT\Schema(
    schema: 'MemberInventory',
    description: 'Represents a member-owned inventory',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1),
        new OAT\Property(property: 'name', type: 'string', example: 'My Shelf'),
        new OAT\Property(property: 'ingredient_count', type: 'integer', example: 24),
    ],
    required: ['id', 'name', 'ingredient_count', 'created_at']
)]
class MemberInventoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ingredient_count' => $this->inventory_ingredients_count ?? $this->inventoryIngredients()->count(),
        ];
    }
}
