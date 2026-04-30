<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Cocktail;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CollectionId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Exception\DomainException;
use BarAssistant\Domain\Cocktail\CocktailCollection;

final class CocktailCollectionTest extends TestCase
{
    public function test_creates_collection_with_deduplicated_cocktails(): void
    {
        $collection = CocktailCollection::create(
            barId: new BarId(10),
            memberId: new MemberId(50),
            name: Name::fromString('Classics'),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: 'Classic cocktails',
            isBarShared: true,
            cocktailIds: [new CocktailId(100), new CocktailId(101), new CocktailId(100)],
        );

        $this->assertNull($collection->getId());
        $this->assertTrue($collection->isTransient());
        $this->assertSame(10, $collection->getBarId()->value);
        $this->assertSame(50, $collection->getMemberId()->value);
        $this->assertSame('Classics', $collection->getName()->toString());
        $this->assertSame('Classic cocktails', $collection->getDescription());
        $this->assertTrue($collection->isBarShared());
        $this->assertSame([100, 101], $this->extractCocktailIds($collection));
    }

    public function test_sets_collection_id(): void
    {
        $collection = $this->createCollection();

        $result = $collection->setId(new CollectionId(12));

        $this->assertSame($collection, $result);
        $this->assertFalse($collection->isTransient());
        $this->assertSame(12, $collection->getId()?->value);
    }

    public function test_cannot_change_id_of_existing_collection(): void
    {
        $collection = $this->createCollection()->setId(new CollectionId(12));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change the ID of an existing collection');

        $collection->setId(new CollectionId(13));
    }

    public function test_cannot_update_details_of_transient_collection(): void
    {
        $collection = $this->createCollection();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot update details of a transient collection');

        $collection->updateDetails(
            name: Name::fromString('Updated'),
            description: 'Updated description',
            isBarShared: true,
        );
    }

    public function test_updates_details_of_existing_collection(): void
    {
        $collection = $this->createCollection()->setId(new CollectionId(12));

        $result = $collection->updateDetails(
            name: Name::fromString('Updated classics'),
            description: 'Updated description',
            isBarShared: true,
        );

        $this->assertSame($collection, $result);
        $this->assertSame('Updated classics', $collection->getName()->toString());
        $this->assertSame('Updated description', $collection->getDescription());
        $this->assertTrue($collection->isBarShared());
        $this->assertNotNull($collection->getRecordTimestamps()->getUpdatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $collection->getRecordTimestamps()->getUpdatedAt());
    }

    public function test_sync_cocktails_deduplicates_without_marking_transient_collection_updated(): void
    {
        $collection = $this->createCollection();

        $collection->syncCocktails([new CocktailId(201), new CocktailId(202), new CocktailId(201)]);

        $this->assertSame([201, 202], $this->extractCocktailIds($collection));
        $this->assertNull($collection->getRecordTimestamps()->getUpdatedAt());
    }

    public function test_sync_cocktails_updates_timestamp_for_existing_collection(): void
    {
        $collection = $this->createCollection()->setId(new CollectionId(12));

        $result = $collection->syncCocktails([new CocktailId(301), new CocktailId(302), new CocktailId(301)]);

        $this->assertSame($collection, $result);
        $this->assertSame([301, 302], $this->extractCocktailIds($collection));
        $this->assertNotNull($collection->getRecordTimestamps()->getUpdatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $collection->getRecordTimestamps()->getUpdatedAt());
    }

    private function createCollection(): CocktailCollection
    {
        return CocktailCollection::create(
            barId: new BarId(10),
            memberId: new MemberId(50),
            name: Name::fromString('Classics'),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: 'Classic cocktails',
            isBarShared: false,
            cocktailIds: [new CocktailId(100), new CocktailId(101)],
        );
    }

    /**
     * @return int[]
     */
    private function extractCocktailIds(CocktailCollection $collection): array
    {
        return array_map(static fn (CocktailId $cocktailId): int => $cocktailId->value, $collection->getCocktailIds());
    }
}
