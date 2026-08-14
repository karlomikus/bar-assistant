<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Cocktail;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\Dilution;
use BarAssistant\Domain\Cocktail\MethodId;
use BarAssistant\Domain\Event\DomainEvent;
use BarAssistant\Domain\DomainEventDispatcher;
use BarAssistant\Domain\Event\EventSubscriber;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Cocktail\Event\CocktailMethodUpdated;

final class CocktailMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DomainEventDispatcher::reset();
    }

    protected function tearDown(): void
    {
        DomainEventDispatcher::reset();

        parent::tearDown();
    }

    public function test_creates_cocktail_method(): void
    {
        $recordTimestamps = RecordTimestamps::createdNow();

        $method = CocktailMethod::create(
            barId: new BarId(10),
            name: Name::fromString('Shaken'),
            dilution: Dilution::fromFloat(25.0),
            recordTimestamps: $recordTimestamps,
            description: 'Shake with ice until chilled',
        );

        $this->assertTrue($method->isTransient());
        $this->assertNull($method->getId());
        $this->assertSame(10, $method->getBarId()->value);
        $this->assertSame('Shaken', $method->getName()->toString());
        $this->assertSame(25.0, $method->getDilution()->toFloat());
        $this->assertSame('Shake with ice until chilled', $method->getDescription());
        $this->assertSame($recordTimestamps, $method->getRecordTimestamps());
    }

    public function test_sets_method_id(): void
    {
        $method = $this->createMethod();

        $result = $method->setId(new MethodId(15));

        $this->assertSame($method, $result);
        $this->assertFalse($method->isTransient());
        $this->assertSame(15, $method->getId()?->value);
    }

    public function test_cannot_change_id_of_existing_method(): void
    {
        $method = $this->createMethod()->setId(new MethodId(15));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing cocktail method');

        $method->setId(new MethodId(16));
    }

    public function test_cannot_update_details_of_transient_method(): void
    {
        $method = $this->createMethod();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot update details of a transient cocktail method');

        $method->updateDetails(
            name: Name::fromString('Stirred'),
            dilution: Dilution::fromFloat(15.0),
            description: 'Stir with ice',
        );
    }

    public function test_updates_details_and_publishes_event(): void
    {
        $method = $this->createMethod()->setId(new MethodId(15));
        $subscriber = new class () implements EventSubscriber {
            public ?DomainEvent $capturedEvent = null;

            public function handle(DomainEvent $event): void
            {
                $this->capturedEvent = $event;
            }

            public function subscribedTo(): array
            {
                return ['cocktailMethodUpdated'];
            }
        };

        DomainEventDispatcher::instance()->subscribe($subscriber);

        $result = $method->updateDetails(
            name: Name::fromString('Stirred'),
            dilution: Dilution::fromFloat(15.0),
            description: 'Stir with ice until chilled',
        );

        $this->assertSame($method, $result);
        $this->assertSame('Stirred', $method->getName()->toString());
        $this->assertSame(15.0, $method->getDilution()->toFloat());
        $this->assertSame('Stir with ice until chilled', $method->getDescription());
        $this->assertNotNull($method->getRecordTimestamps()->getUpdatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $method->getRecordTimestamps()->getUpdatedAt());

        $this->assertInstanceOf(CocktailMethodUpdated::class, $subscriber->capturedEvent);
        $this->assertSame(10, $subscriber->capturedEvent->barId);
        $this->assertSame(15, $subscriber->capturedEvent->methodId);
        $this->assertSame(25.0, $subscriber->capturedEvent->previousDilutionPercentage);
        $this->assertSame(15.0, $subscriber->capturedEvent->currentDilutionPercentage);
    }

    private function createMethod(): CocktailMethod
    {
        return CocktailMethod::create(
            barId: new BarId(10),
            name: Name::fromString('Shaken'),
            dilution: Dilution::fromFloat(25.0),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: 'Shake with ice until chilled',
        );
    }
}
