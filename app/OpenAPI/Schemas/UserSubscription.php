<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['prices', 'customer', 'subscription'])]
class UserSubscription
{
    #[OAT\Property()]
    public string $prices;

    /** @var array<mixed> */
    #[OAT\Property(items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(property: 'paddle_id', type: 'string'),
        new OAT\Property(property: 'paddle_email', type: 'string'),
        new OAT\Property(property: 'paddle_name', type: 'string'),
    ], required: ['paddle_id', 'paddle_email', 'paddle_name']))]
    public array $customer;

    #[OAT\Property()]
    public ?Subscription $subscription;
}
