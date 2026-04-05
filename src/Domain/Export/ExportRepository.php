<?php

declare(strict_types=1);

namespace BarAssistant\Domain\Export;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\User\UserId;

interface ExportRepository
{
    public function findById(ExportId $id): ?Export;

    public function findByUserId(UserId $userId): array;

    public function findByBarId(BarId $barId): array;

    public function save(Export $export): Export;

    public function delete(ExportId $id): void;
}
