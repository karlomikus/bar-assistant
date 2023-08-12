<?php

declare(strict_types=1);

namespace Kami\Cocktail\UnitConverter;

class Dash extends Unit
{
    public function toMl(): Ml
    {
        return new Ml($this->getValue() * 0.3125);
    }
}
