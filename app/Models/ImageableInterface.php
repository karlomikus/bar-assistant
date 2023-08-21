<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

interface ImageableInterface
{
    public function getUploadPath(): string;
}
