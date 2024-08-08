<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class Note
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Note text')]
    public string $note;
    #[OAT\Property(property: 'user_id', example: 1)]
    public int $userId;
    #[OAT\Property(property: 'created_at', example: '2022-01-01T00:00:00+00:00', format: 'date-time')]
    public string $createdAt;
}
