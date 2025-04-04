<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\ValueObjects;

class CalculatorResult
{
    /** @var array<string, string> */
    public array $inputs = [];

    /** @var array<string, string> */
    public array $results = [];
}
