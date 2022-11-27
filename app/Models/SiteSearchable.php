<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

interface SiteSearchable
{
    public function toSiteSearchArray(): array;
}
