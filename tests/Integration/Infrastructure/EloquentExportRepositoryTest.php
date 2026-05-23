<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Export\Export;
use BarAssistant\Domain\Export\ExportId;
use Kami\Cocktail\Models\Export as ModelExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentExportRepository;

final class EloquentExportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_and_finds_export(): void
    {
        $membership = $this->setupBarMembership();
        $repository = new EloquentExportRepository();

        $export = Export::create(
            barId: new BarId($membership->bar_id),
            createdUserId: new UserId($membership->user_id),
            filename: 'bar-export.json',
        );
        $export->markAsDone();

        $savedExport = $repository->save($export);

        $this->assertNotNull($savedExport->getId());
        $this->assertDatabaseHas('exports', [
            'id' => $savedExport->getId()?->value,
            'bar_id' => $membership->bar_id,
            'created_user_id' => $membership->user_id,
            'filename' => 'bar-export.json',
            'is_done' => true,
        ]);

        $foundExport = $repository->findById($savedExport->getId() ?? new ExportId(0));

        $this->assertNotNull($foundExport);
        $this->assertSame($savedExport->getId()?->value, $foundExport->getId()?->value);
        $this->assertSame('bar-export.json', $foundExport->getFilename());
        $this->assertTrue($foundExport->isDone());
    }

    public function test_it_deletes_export(): void
    {
        $membership = $this->setupBarMembership();
        $export = ModelExport::factory()->create([
            'bar_id' => $membership->bar_id,
            'created_user_id' => $membership->user_id,
        ]);

        $repository = new EloquentExportRepository();
        $repository->delete(new ExportId($export->id));

        $this->assertDatabaseMissing('exports', ['id' => $export->id]);
    }
}
