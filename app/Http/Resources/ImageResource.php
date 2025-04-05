<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Resources;

use OpenApi\Attributes as OAT;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Kami\Cocktail\Models\Image
 */
#[OAT\Schema(
    schema: 'Image',
    description: 'Image attached to a specific resource',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 1, description: 'The ID of the image'),
        new OAT\Property(property: 'file_path', type: 'string', example: 'cocktails/1/image.jpg', description: 'The file path of the image'),
        new OAT\Property(property: 'url', type: 'string', nullable: true, example: 'http://example.com/uploads/cocktails/1/image.jpg', description: 'The URL of the image'),
        new OAT\Property(property: 'thumb_url', type: 'string', nullable: true, example: 'http://example.com/uploads/cocktails/1/thumb', description: 'The thumbnail URL of the image'),
        new OAT\Property(property: 'copyright', type: 'string', nullable: true, example: 'Image copyright', description: 'The copyright information of the image'),
        new OAT\Property(property: 'sort', type: 'integer', example: 1, description: 'The sort order of the image'),
        new OAT\Property(property: 'placeholder_hash', type: 'string', nullable: true, example: '1QcSHQRnh493V4dIh4eXh1h4kJUI', description: 'The placeholder hash for the image'),
    ],
    required: ['id', 'file_path', 'url', 'copyright', 'sort', 'placeholder_hash']
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
            'id' => $this->id,
            'file_path' => $this->file_path,
            'url' => $this->getImageUrl(),
            'thumb_url' => $this->getImageThumbUrl(),
            'copyright' => $this->copyright,
            'sort' => $this->sort,
            'placeholder_hash' => $this->placeholder_hash,
        ];
    }
}
