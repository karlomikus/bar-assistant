<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Bar;

enum MemberRole
{
    case Admin;
    case Moderator;
    case General;
    case Guest;

    public static function fromString(string $input): self
    {
        return match (strtolower($input)) {
            'admin' => self::Admin,
            'general' => self::General,
            'guest' => self::Guest,
            'moderator' => self::Moderator,
            default => self::Guest
        };
    }
}
