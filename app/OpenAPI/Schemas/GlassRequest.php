<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
class GlassRequest
{
    #[OAT\Property(example: 'Lowball')]
    public string $name;
    #[OAT\Property(example: 'Glass for smaller cocktails')]
    public ?string $description = null;
    #[OAT\Property(example: 120.0)]
    public ?float $volume = null;
    #[OAT\Property(property: 'volume_units', example: 'ml')]
    public ?string $volumeUnits = null;
    /** @var array<int> */
    #[OAT\Property(items: new OAT\Items(type: 'integer'), description: 'Existing image ids')]
    public array $images = [];

    public static function fromLaravelRequest(Request $request): self
    {
        $result = new self();

        $result->name = $request->input('name');
        $result->description = $request->input('description');
        $result->volume = $request->float('volume');
        $result->volumeUnits = $request->input('volume_units');
        $result->images = array_map(intval(...), $request->input('images', []));

        return $result;
    }
}
