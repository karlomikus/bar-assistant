<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

enum MemberRole
{
    case Admin;
    case General;
    case Guest;

    public static function fromString(string $input): self
    {
        return match (strtolower($input)) {
            'admin' => self::Admin,
            'general' => self::General,
            'guest' => self::Guest,
            default => self::Guest
        };
    }

    public static function fromInt(int $input): self
    {
        return match ($input) {
            1 => self::Admin,
            3 => self::General,
            4 => self::Guest,
            default => self::Guest
        };
    }
}
