<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources\Public;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Image
 */
#[OAT\Schema(
    schema: 'PublicImageResource',
    description: 'Public details about an image',
    properties: [
       new OAT\Property(property: 'sort', type: 'integer', example: 1, description: 'Sort order of the image'),
       new OAT\Property(property: 'placeholder_hash', type: 'string', example: 'abc123', description: 'Placeholder hash for the image, used for lazy loading'),
       new OAT\Property(property: 'url', type: 'string', format: 'uri', example: 'https://example.com/image.jpg', description: 'URL of the image'),
       new OAT\Property(property: 'copyright', type: 'string', nullable: true, example: 'Author name', description: 'Copyright information for the image'),
    ],
    required: ['sort', 'placeholder_hash', 'url', 'copyright'],
)]
class ImageResource extends JsonResource
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
            'sort' => $this->sort,
            'placeholder_hash' => $this->placeholder_hash,
            'url' => $this->getImageUrl(),
            'copyright' => $this->copyright,
        ];
    }
}
