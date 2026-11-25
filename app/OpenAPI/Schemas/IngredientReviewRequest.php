<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['content'])]
class IngredientReviewRequest
{
    #[OAT\Property(example: 'Smoky and complex, great value.')]
    public string $content;

    #[OAT\Property(type: 'string', nullable: true, enum: ['avoid', 'decent', 'recommend'], example: 'recommend')]
    public ?string $recommendation = null;
}
