<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsMarkdown
{
    public function toMarkdown(): string;
}
