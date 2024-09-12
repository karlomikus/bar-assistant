<?php

declare(strict_types=1);

namespace Kami\Cocktail\External;

interface SupportsDraft2
{
    /**
     * @param array<mixed> $sourceArray
     */
    public static function fromDraft2Array(array $sourceArray): self;

    /**
     * @return array<mixed>
     */
    public function toDraft2Array(): array;
}
