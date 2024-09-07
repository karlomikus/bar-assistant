<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsDraft2
{
    public static function fromDraft2Array(array $sourceArray): self;
    public function toDraft2Array(): array;
}
