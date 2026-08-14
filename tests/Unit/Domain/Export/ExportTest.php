<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Export;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Export\Export;
use BarAssistant\Domain\Export\ExportId;

final class ExportTest extends TestCase
{
    public function test_can_create_export(): void
    {
        $barId = new BarId(1);
        $userId = new UserId(1);
        $filename = 'test.csv';

        $export = Export::create($barId, $userId, $filename);

        $this->assertEquals($barId, $export->getBarId());
        $this->assertEquals($userId, $export->getCreatedUserId());
        $this->assertEquals($filename, $export->getFilename());
        $this->assertFalse($export->isDone());
        $this->assertTrue($export->isTransient());
    }

    public function test_can_mark_as_done(): void
    {
        $export = Export::create(new BarId(1), new UserId(1), 'test.csv');
        $export->markAsDone();

        $this->assertTrue($export->isDone());
    }

    public function test_can_set_id(): void
    {
        $export = Export::create(new BarId(1), new UserId(1), 'test.csv');
        $exportId = new ExportId(1);
        $export->setId($exportId);

        $this->assertFalse($export->isTransient());
        $this->assertEquals($exportId, $export->getId());
    }

    public function test_cannot_set_id_twice(): void
    {
        $export = Export::create(new BarId(1), new UserId(1), 'test.csv');
        $export->setId(new ExportId(1));

        $this->expectException(\BarAssistant\Domain\Exception\DomainException::class);
        $export->setId(new ExportId(2));
    }
}
