<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(description: 'Image attached to a specific resource', required: ['id', 'file_path', 'url', 'copyright', 'sort', 'placeholder_hash'])]
class Image
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(property: 'file_path', example: 'cocktails/1/image.jpg')]
    public string $filePath;
    #[OAT\Property(example: 'http://example.com/uploads/cocktails/1/image.jpg')]
    public string $url;
    #[OAT\Property(property: 'thumb_url', example: 'http://example.com/uploads/cocktails/1/thumb')]
    public string $thumbUrl;
    #[OAT\Property(example: 'Image copyright')]
    public ?string $copyright = null;
    #[OAT\Property(example: 1)]
    public int $sort;
    #[OAT\Property(property: 'placeholder_hash', example: '1QcSHQRnh493V4dIh4eXh1h4kJUI')]
    public ?string $placeholderHash = null;
}
