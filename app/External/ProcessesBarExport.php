<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface ProcessesBarExport
{
    public function process(int $barId, ?string $filename = null): string;
}
