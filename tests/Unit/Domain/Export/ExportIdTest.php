<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Export;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Export\ExportId;

final class ExportIdTest extends TestCase
{
    public function test_can_create_export_id(): void
    {
        $id = new ExportId(1);
        $this->assertEquals(1, $id->value);
    }
}
