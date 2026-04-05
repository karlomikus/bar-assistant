<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Export;

interface ExportRepository
{
    public function findById(ExportId $id): ?Export;

    public function save(Export $export): Export;

    public function delete(ExportId $id): void;
}
