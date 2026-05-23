<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use DateTimeImmutable;
use Brick\Money\Currency;
use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Bar\BarSettings;
use Kami\Cocktail\Models\Bar as BarModel;
use BarAssistant\Domain\Common\RecordTimestamps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentBarRepository;

final class EloquentBarRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_bar(): void
    {
        $repository = new EloquentBarRepository();
        $membership = $this->setupBarMembership();

        $bar = Bar::create(
            name: Name::fromString('Bar name'),
            subtitle: 'Dolor sit amet',
            description: 'Lorem ipsum',
            authors: Authors::createdBy(new UserId($membership->user_id))->updatedBy(new UserId($membership->user_id)),
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 12:00:00'))->updatedAt(new DateTimeImmutable('2026-01-01 12:00:00')),
            settings: BarSettings::create(
                isInviteCodeEnabled: true,
                defaultUnits: Unit::from('ml'),
                defaultCurrency: Currency::of('EUR'),
            ),
        );

        $bar = $repository->save($bar);

        $this->assertDatabaseHas('bars', [
            'id' => $bar->getId()->value,
            'name' => 'Bar name',
            'subtitle' => 'Dolor sit amet',
            'description' => 'Lorem ipsum',
            'status' => 'active',
            'is_public' => false,
            'created_at' => '2025-01-01 12:00:00',
            'updated_at' => '2026-01-01 12:00:00',
            'created_user_id' => $membership->user_id,
            'updated_user_id' => $membership->user_id,
        ]);

        $model = BarModel::find($bar->getId()->value);
        $this->assertNotNull($model->invite_code);
        $this->assertSame('ml', $model->settings['default_units']);
        $this->assertSame('EUR', $model->settings['default_currency']);
    }

    public function test_it_updates_bar(): void
    {
        $repository = new EloquentBarRepository();
        $bar = $this->createPersistedBar('Original name');
        $membership = $this->setupBarMembership();

        $bar->updateDetails(
            Name::fromString('New name'),
            new UserId($membership->user_id),
            'New subtitle',
            'New description'
        );

        $repository->save($bar);

        $this->assertDatabaseHas('bars', [
            'id' => $bar->getId()->value,
            'name' => 'New name',
            'subtitle' => 'New subtitle',
            'description' => 'New description',
        ]);
    }

    public function test_it_finds_bar_by_id(): void
    {
        $repository = new EloquentBarRepository();
        $persistedBar = $this->createPersistedBar('Find me');

        $foundBar = $repository->findById($persistedBar->getId());

        $this->assertNotNull($foundBar);
        $this->assertSame('Find me', (string) $foundBar->getName());
    }

    public function test_it_returns_null_for_non_existent_bar(): void
    {
        $repository = new EloquentBarRepository();
        $foundBar = $repository->findById(new BarId(9999));

        $this->assertNull($foundBar);
    }

    public function test_it_deletes_bar(): void
    {
        $repository = new EloquentBarRepository();
        $bar = $this->createPersistedBar('Delete me');

        $repository->delete($bar->getId());

        $this->assertDatabaseMissing('bars', ['id' => $bar->getId()->value]);
    }

    public function test_it_handles_deleting_non_existent_bar(): void
    {
        $repository = new EloquentBarRepository();
        $repository->delete(new BarId(9999));

        $this->assertTrue(true); // Should not throw exception
    }

    private function createPersistedBar(string $name = 'Bar name'): Bar
    {
        $membership = $this->setupBarMembership();

        $bar = Bar::create(
            name: Name::fromString($name),
            subtitle: 'Dolor sit amet',
            description: 'Lorem ipsum',
            authors: Authors::createdBy(new UserId($membership->user_id))->updatedBy(new UserId($membership->user_id)),
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 12:00:00')),
            settings: BarSettings::create(
                isInviteCodeEnabled: false,
                defaultUnits: Unit::from('ml'),
                defaultCurrency: Currency::of('EUR'),
            ),
        );

        return (new EloquentBarRepository())->save($bar);
    }
}
