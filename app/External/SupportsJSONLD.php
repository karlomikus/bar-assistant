<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsJSONLD
{
    public function toJSONLD(): string;
}
