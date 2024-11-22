<?php

declare(strict_types=1);

namespace Kami\Cocktail\Models;

use DateTimeImmutable;

final class FileToken
{
    public static function generate(int $id, string $filename, DateTimeImmutable $expires): string
    {
        $payload = [
            'res' => $filename,
        ];

        $payload = urldecode(http_build_query($payload));
        $payload = implode("\n", [$id, $expires->getTimestamp(), $payload]);

        return hash_hmac('sha256', $payload, config('app.key'));
    }

    public static function check(string $token, int $id, string $filename, DateTimeImmutable $expires): bool
    {
        if ((new DateTimeImmutable()) > $expires) {
            return false;
        }

        return $token === self::generate($id, $filename, $expires);
    }
}
