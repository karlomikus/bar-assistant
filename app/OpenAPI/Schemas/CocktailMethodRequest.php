<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'dilution_percentage'])]
class CocktailMethodRequest
{
    #[OAT\Property(example: 'Shake')]
    public string $name;
    #[OAT\Property(property: 'dilution_percentage', example: 20)]
    public float $dilutionPercentage;
    #[OAT\Property(example: 'Shake with ice to chill and dilute')]
    public ?string $description = null;

    public static function fromLaravelRequest(Request $request): self
    {
        $result = new self();

        $result->name = $request->input('name');
        $result->dilutionPercentage = $request->float('dilution_percentage');
        $result->description = $request->input('description');

        return $result;
    }
}
