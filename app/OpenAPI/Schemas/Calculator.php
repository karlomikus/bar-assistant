<?php

declare(strict_types=1);

namespace Kami\Cocktail\OpenAPI\Schemas;

use OpenApi\Attributes as OAT;

#[OAT\Schema(required: ['id', 'name', 'blocks'])]
class Calculator
{
    #[OAT\Property(example: 1)]
    public int $id;
    #[OAT\Property(example: 'Calculator name')]
    public string $name;
    #[OAT\Property(example: 'Calculator description')]
    public ?string $description;
    /** @var array<CalculatorBlock> */
    #[OAT\Property(type: 'array', items: new OAT\Items(type: CalculatorBlock::class))]
    public array $blocks;
}
