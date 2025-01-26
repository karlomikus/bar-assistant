<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['name'])]
readonly class CalculatorRequest
{
    /**
     * @param array<CalculatorBlockRequest> $blocks
     */
    public function __construct(
        #[OAT\Property()]
        public string $name,
        #[OAT\Property(items: new OAT\Items(type: CalculatorBlockRequest::class))]
        public array $blocks,
        #[OAT\Property()]
        public ?string $description = null,
    ) {
    }

    /**
     * @param array<mixed> $source
     */
    public static function fromArray(array $source): self
    {
        $blocks = [];
        foreach ($source['blocks'] ?? [] as $block) {
            $blocks[] = CalculatorBlockRequest::fromArray($block);
        }

        return new self(
            $source['name'],
            $blocks,
            $source['description'] ?? null,
        );
    }
}
