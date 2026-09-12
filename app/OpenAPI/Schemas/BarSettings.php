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
    #[OAT\Property(property: 'standard_drink_region', type: 'string', enum: ['us', 'uk'], example: 'uk', description: 'Convention used to express the alcohol content of cocktails. `uk` for UK alcohol units, `us` for US standard drinks.')]
    public ?string $standardDrinkRegion = null;

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'default_units' => $this->defaultUnits,
            'default_currency' => $this->defaultCurrency,
            'standard_drink_region' => $this->standardDrinkRegion,
        ];
    }
}
