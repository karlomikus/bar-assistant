<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class Subscription
{
    #[OAT\Property()]
    public string $type;

    #[OAT\Property()]
    public string $paddle_id;

    #[OAT\Property()]
    public string $status;

    #[OAT\Property(format: 'date-time')]
    public string $created_at;

    #[OAT\Property(format: 'date-time')]
    public ?string $updated_at;

    #[OAT\Property(format: 'date-time')]
    public ?string $paused_at;

    #[OAT\Property(format: 'date-time')]
    public ?string $ends_at;

    #[OAT\Property()]
    public bool $past_due;

    #[OAT\Property()]
    public bool $is_recurring;

    /** @var array<mixed> */
    #[OAT\Property(items: new OAT\Items(type: 'object', properties: [
        new OAT\Property(property: 'currency', type: 'string'),
        new OAT\Property(property: 'amount', type: 'string'),
        new OAT\Property(property: 'date', type: 'string', format: 'date-time'),
    ]))]
    public array $next_billed_at;

    #[OAT\Property(format: 'uri')]
    public string $update_payment_url;

    #[OAT\Property(format: 'uri')]
    public string $cancel_url;

    /** @var Transaction[] */
    #[OAT\Property()]
    public array $transactions;
}
