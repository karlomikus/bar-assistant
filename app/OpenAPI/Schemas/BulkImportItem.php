<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['status'])]
class BulkImportItem
{
    #[OAT\Property(description: 'Import outcome status', type: 'string', example: 'created', enum: ['created', 'skipped', 'overwritten', 'failed'])]
    public string $status;

    #[OAT\Property(description: 'Basic cocktail data when applicable', type: CocktailBasicResource::class, nullable: true)]
    public ?CocktailBasicResource $cocktail = null;

    #[OAT\Property(description: 'Source recipe name when known', type: 'string', nullable: true, example: 'Negroni')]
    public ?string $name = null;

    #[OAT\Property(description: 'Error message when failed', type: 'string', nullable: true, example: 'Validation failed: missing ingredients array')]
    public ?string $error = null;

    #[OAT\Property(description: 'Original index in the submitted array', type: 'integer', nullable: true, example: 0)]
    public ?int $index = null;
}


