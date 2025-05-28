<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use Illuminate\Support\Str;
use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Services\Feeds\FeedsRecipe
 */
#[OAT\Schema(
    title: 'FeedsRecipe',
    schema: 'FeedsRecipe',
    description: 'Represents a recipe from an RSS/Atom feed',
    properties: [
        new \OpenApi\Attributes\Property(property: 'source', type: 'string', description: 'The source of the recipe'),
        new \OpenApi\Attributes\Property(property: 'title', type: 'string', description: 'The title of the recipe'),
        new \OpenApi\Attributes\Property(property: 'description', type: 'string', nullable: true, description: 'The description of the recipe'),
        new \OpenApi\Attributes\Property(property: 'link', type: 'string', description: 'The link to the recipe'),
        new \OpenApi\Attributes\Property(property: 'date', type: 'string', nullable: true, format: 'date-time', description: 'The date the recipe was modified'),
        new \OpenApi\Attributes\Property(property: 'image', type: 'string', nullable: true, description: 'The image URL of the recipe'),
        new \OpenApi\Attributes\Property(property: 'supports_recipe_import', type: 'boolean', description: 'Indicates if the recipe supports import into the application'),
    ],
    required: ['source', 'title', 'link', 'date', 'image', 'description'],
)]
class FeedRecipeResource extends JsonResource
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
            'source' => $this->source,
            'title' => $this->title,
            'description' => Str::limit($this->description, 250, '...'),
            'link' => $this->link,
            'date' => $this->dateModified?->format(DATE_ATOM),
            'image' => $this->image,
            'supports_recipe_import' => $this->supportsRecipeImport,
        ];
    }
}
