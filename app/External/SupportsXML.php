<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsXML
{
    public function toXML(): string;
}
