<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use BarAssistant\Domain\Bar\Bar;
use Tests\TestCase;
use BarAssistant\Domain\Bar\BarSettings;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use Brick\Money\Currency;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kami\Cocktail\Infrastructure\EloquentBarRepository;
use Kami\Cocktail\Models\Bar as BarModel;

final class EloquentBarRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_bar(): void
    {
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

        $repository = new EloquentBarRepository();
        $bar = $repository->save($bar);

        $this->assertDatabaseCount('bars', 2);
        $this->assertDatabaseHas('bars', [
            'id' => 2,
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

        $model = BarModel::find(2);
        $this->assertNotNull($model->invite_code);
        $this->assertSame('ml', $model->settings['default_units']);
        $this->assertSame('EUR', $model->settings['default_currency']);
    }
}
