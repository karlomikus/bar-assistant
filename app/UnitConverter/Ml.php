<?php

declare(strict_types=1);

namespace Kami\Cocktail\UnitConverter;

class Ml extends Unit
{
    public function toOz(): Oz
    {
        return new Oz($this->getValue() / 30);
    }

    public function toCl(): Cl
    {
        return new Cl($this->getValue() / 10);
    }
}
