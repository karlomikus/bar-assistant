<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsYAML
{
    public function toYAML(): string;
}
