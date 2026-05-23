<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Cocktail;

use PHPUnit\Framework\TestCase;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CollectionId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\CocktailCollection;
use BarAssistant\Application\Cocktail\CollectionService;
use BarAssistant\Application\Cocktail\DTO\CollectionResult;
use BarAssistant\Application\Cocktail\DTO\CreateCollection;
use BarAssistant\Application\Cocktail\DTO\UpdateCollection;
use Tests\Infrastructure\InMemoryCocktailCollectionRepository;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Cocktail\DTO\SyncCollectionCocktails;

final class CollectionServiceTest extends TestCase
{
    private InMemoryCocktailCollectionRepository $collectionRepository;
    private CollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collectionRepository = new InMemoryCocktailCollectionRepository([
            1 => CocktailCollection::create(
                barId: new BarId(10),
                memberId: new MemberId(50),
                name: Name::fromString('Classics'),
                recordTimestamps: RecordTimestamps::createdNow(),
                description: 'Classic cocktails',
                isBarShared: false,
                cocktailIds: [new CocktailId(100), new CocktailId(101)],
            )->setId(new CollectionId(1)),
        ]);

        $this->service = new CollectionService($this->collectionRepository);
    }

    public function test_creates_collection(): void
    {
        $result = $this->service->createCollection(new CreateCollection(
            barId: 10,
            memberId: 50,
            name: 'Summer drinks',
            description: 'Refreshing cocktails',
            isBarShared: true,
            cocktailIds: [200, 201, 201],
        ));

        $this->assertInstanceOf(CollectionResult::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame(10, $result->barId);
        $this->assertSame(50, $result->memberId);
        $this->assertSame('Summer drinks', $result->name);
        $this->assertSame('Refreshing cocktails', $result->description);
        $this->assertTrue($result->isBarShared);
        $this->assertSame([200, 201], $result->cocktailIds);
    }

    public function test_updates_collection(): void
    {
        $result = $this->service->updateCollection(new UpdateCollection(
            collectionId: 1,
            name: 'Updated classics',
            description: 'Updated description',
            isBarShared: true,
        ));

        $this->assertSame(1, $result->id);
        $this->assertSame('Updated classics', $result->name);
        $this->assertSame('Updated description', $result->description);
        $this->assertTrue($result->isBarShared);
    }

    public function test_syncs_cocktails_in_collection(): void
    {
        $result = $this->service->syncCocktails(new SyncCollectionCocktails(
            collectionId: 1,
            cocktailIds: [501, 502, 502],
        ));

        $this->assertSame([501, 502], $result->cocktailIds);
    }

    public function test_syncs_empty_cocktail_list(): void
    {
        $result = $this->service->syncCocktails(new SyncCollectionCocktails(
            collectionId: 1,
            cocktailIds: [],
        ));

        $this->assertSame([], $result->cocktailIds);
    }

    public function test_deletes_collection(): void
    {
        $this->service->deleteCollection(1);

        $this->assertNull($this->collectionRepository->findById(new CollectionId(1)));
    }

    public function test_cannot_update_non_existing_collection(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->service->updateCollection(new UpdateCollection(
            collectionId: 999,
            name: 'Unknown',
        ));
    }

    public function test_cannot_sync_non_existing_collection(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->service->syncCocktails(new SyncCollectionCocktails(
            collectionId: 999,
            cocktailIds: [1],
        ));
    }

    public function test_cannot_delete_non_existing_collection(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->service->deleteCollection(999);
    }
}
