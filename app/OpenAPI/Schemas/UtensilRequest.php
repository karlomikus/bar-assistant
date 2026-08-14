<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class UtensilRequest
{
    #[OAT\Property(example: 'Shaker')]
    public string $name;
    #[OAT\Property(example: 'Used to shake ingredients')]
    public ?string $description = null;

    public static function fromLaravelRequest(Request $request): self
    {
        $result = new self();

        $result->name = $request->input('name');
        $result->description = $request->input('description');

        return $result;
    }
}
