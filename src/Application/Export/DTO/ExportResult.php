<?php

declare(strict_types=1);

namespace BarAssistant\Application\Export\DTO;

use BarAssistant\Domain\Export\Export;

final readonly class ExportResult
{
    public function __construct(
        public int $id,
        public int $barId,
        public string $filename,
        public int $createdUserId,
        public bool $isDone,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromExport(Export $export): self
    {
        return new self(
            id: $export->getId()->value ?? 0,
            barId: $export->getBarId()->value,
            filename: $export->getFilename(),
            createdUserId: $export->getCreatedUserId()->value,
            isDone: $export->isDone(),
            createdAt: $export->getRecordTimestamps()->getCreatedAt()->format('c'),
            updatedAt: $export->getRecordTimestamps()->getUpdatedAt()?->format('c'),
        );
    }
}
