<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;
use Kami\Cocktail\Models\Enums\CalculatorBlockTypeEnum;

#[OAT\Schema(required: ['sort', 'label', 'variable_name', 'value', 'description', 'settings'])]
class CalculatorBlock
{
    #[OAT\Property(example: 1)]
    public int $sort;
    #[OAT\Property(example: 'Short label')]
    public string $label;
    #[OAT\Property(example: 'var-name', property: 'variable_name')]
    public string $variableName;
    #[OAT\Property(example: 'sugar * 2')]
    public string $value;
    #[OAT\Property(example: 'eval')]
    public CalculatorBlockTypeEnum $type;
    #[OAT\Property(example: 'Short description')]
    public ?string $description;
    #[OAT\Property()]
    public CalculatorBlockSettings $settings;
}
