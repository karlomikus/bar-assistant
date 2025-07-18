<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;

#[OAT\Schema(required: ['label', 'variable_name', 'value', 'type', 'settings', 'sort'])]
readonly class CalculatorBlockRequest
{
    public function __construct(
        #[OAT\Property()]
        public string $label,
        #[OAT\Property(property: 'variable_name')]
        public string $variableName,
        #[OAT\Property()]
        public string $value,
        #[OAT\Property()]
        public CalculatorBlockTypeEnum $type,
        #[OAT\Property()]
        public CalculatorBlockSettings $settings,
        #[OAT\Property()]
        public ?string $description = null,
        #[OAT\Property()]
        public int $sort = 1,
    ) {
    }

    /**
     * @param array<string, mixed> $source
     */
    public static function fromArray(array $source): self
    {
        $validSettings = ['suffix', 'prefix', 'decimal_places'];
        $settings = array_intersect_key($source['settings'] ?? [], array_flip($validSettings));

        $settings = new CalculatorBlockSettings();
        $settings->suffix = $source['settings']['suffix'] ?? null;
        $settings->prefix = $source['settings']['prefix'] ?? null;
        $settings->decimalPlaces = $source['settings']['decimal_places'] ?? null;

        return new self(
            $source['label'],
            $source['variable_name'],
            $source['value'] ?? '',
            CalculatorBlockTypeEnum::from($source['type']),
            $settings,
            $source['description'] ?? null,
            (int) $source['sort'],
        );
    }
}
