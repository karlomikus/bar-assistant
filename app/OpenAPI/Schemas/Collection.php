<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class Collection
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Collection name')]
    public string $name;
    #[OAT\Property(example: 'Collection description')]
    public ?string $description = null;
    #[OAT\Property(property: 'is_bar_shared')]
    public bool $isBarShared = false;
    #[OAT\Property(format: 'date-time', example: '2023-05-14T21:23:40.000000Z')]
    public string $createdAt;
    #[OAT\Property(property: 'created_user')]
    public UserBasic $createdUser;
    /** @var CocktailBasic[] */
    #[OAT\Property()]
    public array $cocktails = [];
}
