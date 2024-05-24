<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

enum ExportTypeEnum: string
{
    case JSON = 'json';
    case YAML = 'yml';
}
