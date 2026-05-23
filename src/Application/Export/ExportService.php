<?php

declare(strict_types=1);

namespace BarAssistant\Application\Export;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Export\Export;
use BarAssistant\Domain\Export\ExportId;
use BarAssistant\Domain\Bar\BarRepository;
use BarAssistant\Domain\Export\ExportRepository;
use BarAssistant\Application\Export\DTO\ExportResult;
use BarAssistant\Application\Export\DTO\CreateExportRequest;
use BarAssistant\Application\Exception\EntityNotFoundException;

final readonly class ExportService
{
    public function __construct(
        private ExportRepository $exportRepository,
        private BarRepository $barRepository,
    ) {
    }

    public function createExport(CreateExportRequest $request): ExportResult
    {
        $barId = new BarId($request->barId);
        $bar = $this->barRepository->findById($barId);
        if ($bar === null) {
            throw new EntityNotFoundException('Bar not found');
        }

        $export = Export::create(
            barId: $barId,
            createdUserId: new UserId($request->userId),
            filename: $request->filename,
        );

        $export = $this->exportRepository->save($export);

        return ExportResult::fromExport($export);
    }

    public function deleteExport(int $exportId): void
    {
        $export = $this->exportRepository->findById(new ExportId($exportId));

        if ($export === null) {
            throw new EntityNotFoundException('Export not found');
        }

        $this->exportRepository->delete(new ExportId($exportId));
    }

    public function markAsDone(int $exportId): ExportResult
    {
        $export = $this->exportRepository->findById(new ExportId($exportId));

        if ($export === null) {
            throw new EntityNotFoundException('Export not found');
        }

        $export->markAsDone();
        $export = $this->exportRepository->save($export);

        return ExportResult::fromExport($export);
    }
}
