<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema()]
class BarSettings
{
    #[OAT\Property(property: 'default_units')]
    public ?string $defaultUnits = null;
    #[OAT\Property(property: 'default_currency')]
    public ?string $defaultCurrency = null;

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'default_units' => $this->defaultUnits,
            'default_currency' => $this->defaultCurrency,
        ];
    }
}
