<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class Transaction
{
    #[OAT\Property()]
    public string $total;

    #[OAT\Property()]
    public string $tax;

    #[OAT\Property()]
    public string $currency;

    #[OAT\Property()]
    public string $status;

    #[OAT\Property()]
    public string $invoice_number;

    #[OAT\Property(format: 'uri')]
    public string $url;

    #[OAT\Property(format: 'date-time')]
    public string $billed_at;

    #[OAT\Property(format: 'date-time')]
    public string $created_at;

    #[OAT\Property(format: 'date-time')]
    public ?string $updated_at;
}
