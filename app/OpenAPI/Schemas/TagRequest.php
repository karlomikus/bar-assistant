<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
readonly class TagRequest
{
    public function __construct(
        #[OAT\Property(type: 'string', maxLength: 255, example: 'Floral')]
        public string $name,
    ) {
    }

    public static function fromLaravelRequest(Request $request): self
    {
        return new self(
            name: $request->string('name')->toString(),
        );
    }
}
