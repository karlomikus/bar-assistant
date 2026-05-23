<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'currency'])]
class PriceCategoryRequest
{
    #[OAT\Property(example: 'Amazon (DE)')]
    public string $name;
    #[OAT\Property(example: 'Current price on amazon.de')]
    public ?string $description = null;
    #[OAT\Property(example: 'EUR', format: 'ISO 4217')]
    public string $currency;

    public static function fromLaravelRequest(Request $request): self
    {
        $result = new self();

        $name = $request->input('name');
        $currency = $request->input('currency');
        $description = $request->input('description');
        $result->name = $name;
        $result->currency = $currency;
        $result->description = $description;

        return $result;
    }
}
