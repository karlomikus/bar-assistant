<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class ImageRequest
{
    #[OAT\Property(format: 'binary', description: 'Image file. Required if `image_url` is not set.')]
    public string $image;
    #[OAT\Property(property: 'image_url', example: 'http://example.com/cocktail_image.jpg', description: 'Image URL. Required if `image` is not set.')]
    public ?string $imageUrl = null;
    #[OAT\Property(example: 1)]
    public int $sort = 1;
    #[OAT\Property(example: 'Image copyright')]
    public ?string $copyright = null;
}
