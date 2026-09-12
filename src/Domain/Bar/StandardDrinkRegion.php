<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

enum StandardDrinkRegion: string
{
    case Us = 'us';
    case Uk = 'uk';

    public static function default(): self
    {
        return self::Uk;
    }
}
