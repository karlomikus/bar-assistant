<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\CocktailReview;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CocktailReview
 */
#[OAT\Schema(
    schema: 'Review',
    description: 'Review resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Review ID'),
        new OAT\Property(property: 'content', type: 'string', example: 'A great cocktail.', description: 'Review text content'),
        new OAT\Property(property: 'rating', type: 'number', nullable: true, example: 4.5, description: 'Author current rating for the cocktail, joined live from ratings'),
        new OAT\Property(property: 'author', type: 'object', properties: [
            new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'Author user ID'),
            new OAT\Property(property: 'name', type: 'string', example: 'John Doe', description: 'Author display name'),
        ]),
        new OAT\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2022-01-01T00:00:00+00:00', description: 'Creation date and time'),
        new OAT\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true, example: '2022-01-02T00:00:00+00:00', description: 'Last update date and time'),
    ],
    required: [
        'id',
        'content',
        'rating',
        'author',
        'created_at',
        'updated_at',
    ],
)]
class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'rating' => $this->rating,
            'author' => new UserBasicResource($this->barMembership->user),
            'created_at' => $this->created_at->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
