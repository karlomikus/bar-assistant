<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

interface UploadableInterface
{
    public function getUploadPath(): string;
}
