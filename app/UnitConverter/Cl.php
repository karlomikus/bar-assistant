<?php

declare(strict_types=1);

namespace Kami\Cocktail\UnitConverter;

class Cl extends Unit
{
    public function toMl(): Ml
    {
        return new Ml($this->getValue() * 10);
    }

    public function toOz(): Oz
    {
        return new Oz($this->getValue() / 3);
    }
}
