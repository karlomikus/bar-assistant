<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Export;

use DateTimeImmutable;

final readonly class FileTokenService
{
    public function __construct(
        private string $appKey,
    ) {
    }

    public function generate(int $id, string $filename, DateTimeImmutable $expires): string
    {
        $payload = [
            'res' => $filename,
        ];

        $payload = urldecode(http_build_query($payload));
        $payload = implode("\n", [$id, $expires->getTimestamp(), $payload]);

        return hash_hmac('sha256', $payload, $this->appKey);
    }

    public function check(string $token, int $id, string $filename, DateTimeImmutable $expires): bool
    {
        if ((new DateTimeImmutable()) > $expires) {
            return false;
        }

        return hash_equals(
            $this->generate($id, $filename, $expires),
            $token,
        );
    }
}
