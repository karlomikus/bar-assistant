<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class CalculatorBlockSettings
{
    #[OAT\Property()]
    public ?string $suffix = null;
    #[OAT\Property()]
    public ?string $prefix = null;
    #[OAT\Property(property: 'decimal_places')]
    public ?int $decimalPlaces = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'suffix' => $this->suffix,
            'prefix' => $this->prefix,
            'decimal_places' => $this->decimalPlaces,
        ];
    }
}
