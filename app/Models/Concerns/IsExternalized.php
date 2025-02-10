<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models\Concerns;

interface IsExternalized
{
    public function getExternalId(): string;
}
