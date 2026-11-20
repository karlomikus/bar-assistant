<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class GlassRequest
{
    #[OAT\Property(type: 'string', maxLength: 255, example: 'Lowball')]
    public string $name;
    #[OAT\Property(type: 'string', nullable: true, example: 'Glass for smaller cocktails')]
    public ?string $description = null;
    #[OAT\Property(type: 'number', format: 'float', nullable: true, minimum: 0, example: 120.0)]
    public ?float $volume = null;
    #[OAT\Property(property: 'volume_units', type: 'string', nullable: true, example: 'ml')]
    public ?string $volumeUnits = null;
    /** @var array<int> */
    #[OAT\Property(items: new OAT\Items(type: 'integer'), maxItems: 1, description: 'Existing image ids')]
    public array $images = [];

    public static function fromLaravelRequest(Request $request): self
    {
        $result = new self();

        $result->name = $request->string('name')->toString();
        $result->description = $request->filled('description')
            ? $request->string('description')->toString()
            : null;
        $result->volume = $request->input('volume') !== null
            ? $request->float('volume')
            : null;
        $result->volumeUnits = $request->filled('volume_units')
            ? $request->string('volume_units')->toString()
            : null;

        /** @var array<int> $requestImages */
        $requestImages = $request->input('images', []);
        $result->images = array_map(intval(...), $requestImages);

        return $result;
    }
}
