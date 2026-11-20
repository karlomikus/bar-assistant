<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name', 'dilution_percentage'])]
class CocktailMethodRequest
{
    #[OAT\Property(type: 'string', maxLength: 255, example: 'Shake')]
    public string $name;
    #[OAT\Property(property: 'dilution_percentage', type: 'integer', minimum: 0, maximum: 100, example: 20)]
    public int $dilutionPercentage;
    #[OAT\Property(type: 'string', nullable: true, example: 'Shake with ice to chill and dilute')]
    public ?string $description = null;

    public static function fromLaravelRequest(Request $request): self
    {
        $result = new self();

        $result->name = $request->string('name')->toString();
        $result->dilutionPercentage = $request->integer('dilution_percentage');
        $result->description = $request->filled('description')
            ? $request->string('description')->toString()
            : null;

        return $result;
    }
}
