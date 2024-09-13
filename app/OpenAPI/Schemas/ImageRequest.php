<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['image', 'sort'])]
readonly class ImageRequest
{
    public function __construct(
        #[OAT\Property(format: 'binary', description: 'Image file. Base64 encoded images also supported. Max 50MB')]
        public ?string $image,
        #[OAT\Property(description: 'Existing image id, used to update an existing image')]
        public ?int $id = null,
        #[OAT\Property(example: 1)]
        public int $sort = 1,
        #[OAT\Property(example: 'Image copyright')]
        public ?string $copyright = null,
    ) {
    }
}
