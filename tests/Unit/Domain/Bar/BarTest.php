<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Bar;

use Brick\Money\Currency;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\Bar;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Unit;
use BarAssistant\Domain\User\UserId;
use BarAssistant\Domain\Bar\BarStatus;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Bar\BarSettings;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Ingredient\IngredientId;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Bar\IngredientInventoryStatus;

final class BarTest extends TestCase
{
    public function test_cannot_change_id_of_persisted_bar(): void
    {
        $bar = $this->createBar();

        $bar->setId(new BarId(1));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing bar');

        $bar->setId(new BarId(2));
    }

    public function test_create_assigns_default_settings_and_active_status(): void
    {
        $bar = $this->createBar();

        $this->assertTrue($bar->isTransient());
        $this->assertFalse($bar->isInviteCodeEnabled());
        $this->assertNull($bar->getDefaultUnits());
        $this->assertNull($bar->getDefaultCurrency());
        $this->assertSame(BarStatus::Active, $bar->getStatus());
        $this->assertFalse($bar->isPublic());
    }

    public function test_put_ingredient_in_inventory_replaces_existing_status(): void
    {
        $bar = $this->createBar();
        $ingredientId = new IngredientId(10);

        $bar->putIngredientInInventory($ingredientId, IngredientInventoryStatus::Variant);
        $bar->putIngredientInInventory($ingredientId, IngredientInventoryStatus::InStock);

        $inventory = $bar->getIngredientInventory();

        $this->assertCount(1, $inventory);
        $this->assertTrue($inventory[0]->ingredientId->equals($ingredientId));
        $this->assertTrue($inventory[0]->isInStock());
    }

    public function test_inventory_filters_return_only_matching_statuses(): void
    {
        $bar = $this->createBar();

        $bar->putIngredientInInventory(new IngredientId(10), IngredientInventoryStatus::InStock);
        $bar->putIngredientInInventory(new IngredientId(20), IngredientInventoryStatus::Variant);
        $bar->putIngredientInInventory(new IngredientId(30), IngredientInventoryStatus::Makeable);

        $inStock = $bar->getInStockIngredients();
        $variants = $bar->getVariantIngredients();

        $this->assertCount(1, $inStock);
        $this->assertCount(1, $variants);
        $this->assertSame(10, array_values($inStock)[0]->ingredientId->value);
        $this->assertSame(20, array_values($variants)[0]->ingredientId->value);
    }

    public function test_remove_ingredient_from_inventory_is_noop_when_missing(): void
    {
        $bar = $this->createBar();

        $bar->putIngredientInInventory(new IngredientId(10), IngredientInventoryStatus::InStock);
        $bar->removeIngredientFromInventory(new IngredientId(999));

        $this->assertCount(1, $bar->getIngredientInventory());
        $this->assertSame(10, $bar->getIngredientInventory()[0]->ingredientId->value);
    }

    public function test_update_settings_rejects_transient_bar(): void
    {
        $bar = $this->createBar();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot update settings of a transient bar');

        $bar->updateSettings(BarSettings::create(
            isInviteCodeEnabled: true,
            defaultUnits: Unit::from('ml'),
            defaultCurrency: Currency::of('EUR'),
        ));
    }

    public function test_update_settings_updates_persisted_bar(): void
    {
        $bar = $this->createPersistedBar();

        $bar->updateSettings(BarSettings::create(
            isInviteCodeEnabled: true,
            defaultUnits: Unit::from('oz'),
            defaultCurrency: Currency::of('USD'),
        ));

        $this->assertTrue($bar->isInviteCodeEnabled());
        $this->assertSame('oz', (string) $bar->getDefaultUnits());
        $this->assertSame('USD', $bar->getDefaultCurrency()?->getCurrencyCode());
    }

    public function test_update_details_rejects_transient_bar(): void
    {
        $bar = $this->createBar();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot update details of a transient bar');

        $bar->updateDetails(
            name: Name::fromString('Updated bar'),
            updatedBy: new UserId(2),
            subtitle: 'Updated subtitle',
            description: 'Updated description',
        );
    }

    public function test_update_details_updates_fields_authors_and_timestamps(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01T10:00:00+00:00');
        $bar = $this->createPersistedBar(recordTimestamps: RecordTimestamps::createdAt($createdAt));

        $bar->updateDetails(
            name: Name::fromString('Evening Bar'),
            updatedBy: new UserId(42),
            subtitle: 'Late service',
            description: 'Focused on stirred drinks',
        );

        $this->assertSame('Evening Bar', $bar->getName()->toString());
        $this->assertSame('Late service', $bar->getSubtitle());
        $this->assertSame('Focused on stirred drinks', $bar->getDescription());
        $this->assertTrue($bar->getAuthors()->isUpdated());
        $this->assertSame(42, $bar->getAuthors()->getUpdatedBy()?->value);
        $this->assertSame($createdAt, $bar->getRecordTimestamps()->getCreatedAt());
        $this->assertTrue($bar->getRecordTimestamps()->wasUpdated());
        $this->assertNotNull($bar->getRecordTimestamps()->getUpdatedAt());
    }

    private function createBar(?RecordTimestamps $recordTimestamps = null): Bar
    {
        return Bar::create(
            name: Name::fromString('Test Bar'),
            authors: Authors::createdBy(new UserId(1)),
            recordTimestamps: $recordTimestamps ?? RecordTimestamps::createdNow(),
        );
    }

    private function createPersistedBar(?RecordTimestamps $recordTimestamps = null): Bar
    {
        $bar = $this->createBar(recordTimestamps: $recordTimestamps);
        $bar->setId(new BarId(1));

        return $bar;
    }
}