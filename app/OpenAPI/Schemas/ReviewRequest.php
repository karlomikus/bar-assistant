<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['content'])]
class ReviewRequest
{
    #[OAT\Property(example: 'A great cocktail, well balanced.')]
    public string $content;
}
