<?php

declare(strict_types=1);

namespace Kami\Cocktail;

use Kami\Cocktail\Models\Bar;
use Kami\Cocktail\Models\BarMembership;

class BarContext
{
    public function __construct(private readonly Bar $currentBar)
    {
    }

    public function getBar(): Bar
    {
        return $this->currentBar;
    }

    public function getCurrentUserBarMembership(): ?BarMembership
    {
        return auth()->user()->getBarMembership($this->currentBar->id);
    }
}
