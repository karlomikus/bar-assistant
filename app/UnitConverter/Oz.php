<?php

declare(strict_types=1);

namespace Kami\Cocktail\UnitConverter;

class Oz extends Unit
{
    public function toMl(): Ml
    {
        return new Ml($this->getValue() * 30);
    }

    public function toCl(): Cl
    {
        return new Cl($this->getValue() * 3);
    }
}
