<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Glass as GlassModel;

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

    public static function fromLaravelRequest(Request $request): self
    {
        $result = new self();

        $result->name = $request->input('name');
        $result->description = $request->input('description');
        $result->volume = $request->float('volume');
        $result->volumeUnits = $request->input('volume_units');

        return $result;
    }

    public function toLaravelModel(?GlassModel $model = null): GlassModel
    {
        $result = $model ?? new GlassModel();

        $result->name = $this->name;
        $result->description = $this->description;
        $result->volume = $this->volume;
        $result->volume_units = $this->volumeUnits;

        return $result;
    }
}
