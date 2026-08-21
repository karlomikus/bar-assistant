<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Common;

use Stringable;
use DomainException;

final readonly class File implements Stringable
{
    /**
     * @param string $path The full filepath including filename and extension
     * @param string $extension The file extension (e.g., jpg, png)
     * @param null|string $placeholderHash A placeholder hash for the file (optional)
     */
    private function __construct(
        public string $path,
        public string $extension,
        public ?string $placeholderHash = null,
        public string $disk = 'uploads',
        public string $storageOrigin = 'owned',
    ) {
        if (trim($path) === '') {
            throw new DomainException('File path cannot be empty');
        }

        if (!in_array($storageOrigin, ['owned', 'catalog'], true)) {
            throw new DomainException('Unsupported file storage origin');
        }
    }

    public static function from(
        string $path,
        string $extension,
        ?string $placeholderHash = null,
        string $disk = 'uploads',
        string $storageOrigin = 'owned',
    ): self {
        return new self($path, $extension, $placeholderHash, $disk, $storageOrigin);
    }

    public function __toString(): string
    {
        return (string) $this->path;
    }
}
