<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use Tests\TestCase;
use DateTimeImmutable;
use BarAssistant\Domain\Bar\BarId;
use Kami\Cocktail\Models\Cocktail;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CollectionId;
use BarAssistant\Domain\Common\RecordTimestamps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use BarAssistant\Domain\Cocktail\CocktailCollection;
use Kami\Cocktail\Models\Collection as ModelCollection;
use Kami\Cocktail\Infrastructure\EloquentCocktailCollectionRepository;

final class EloquentCocktailCollectionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_null_for_non_existent_collection(): void
    {
        $repository = new EloquentCocktailCollectionRepository();

        $collection = $repository->findById(new CollectionId(9999));

        $this->assertNull($collection);
    }

    public function test_it_saves_collection_and_filters_cocktails_from_other_bars(): void
    {
        $membership = $this->setupBarMembership();
        $repository = new EloquentCocktailCollectionRepository();
        $firstAllowedCocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Negroni']);
        $secondAllowedCocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Martini']);
        $foreignCocktail = Cocktail::factory()->create();

        $collection = CocktailCollection::create(
            barId: new BarId($membership->bar_id),
            memberId: new MemberId($membership->id),
            name: Name::fromString('House classics'),
            recordTimestamps: RecordTimestamps::createdAt(new DateTimeImmutable('2025-01-01 12:00:00')),
            description: 'Shared cocktails',
            isBarShared: true,
            cocktailIds: [
                new CocktailId($firstAllowedCocktail->id),
                new CocktailId($foreignCocktail->id),
                new CocktailId($secondAllowedCocktail->id),
                new CocktailId($firstAllowedCocktail->id),
            ],
        );

        $savedCollection = $repository->save($collection);

        $this->assertNotNull($savedCollection->getId());
        $this->assertSame($membership->id, $savedCollection->getMemberId()->value);
        $this->assertSame($membership->bar_id, $savedCollection->getBarId()->value);
        $this->assertSame('House classics', $savedCollection->getName()->toString());
        $this->assertSame('Shared cocktails', $savedCollection->getDescription());
        $this->assertTrue($savedCollection->isBarShared());
        $this->assertSame('2025-01-01 12:00:00', $savedCollection->getRecordTimestamps()->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing(
            [$firstAllowedCocktail->id, $secondAllowedCocktail->id],
            $this->extractCocktailIds($savedCollection),
        );

        $this->assertDatabaseHas('collections', [
            'id' => $savedCollection->getId()?->value,
            'bar_membership_id' => $membership->id,
            'name' => 'House classics',
            'description' => 'Shared cocktails',
            'is_bar_shared' => true,
            'created_at' => '2025-01-01 12:00:00',
        ]);
        $this->assertDatabaseHas('collections_cocktails', [
            'collection_id' => $savedCollection->getId()?->value,
            'cocktail_id' => $firstAllowedCocktail->id,
        ]);
        $this->assertDatabaseHas('collections_cocktails', [
            'collection_id' => $savedCollection->getId()?->value,
            'cocktail_id' => $secondAllowedCocktail->id,
        ]);
        $this->assertDatabaseMissing('collections_cocktails', [
            'collection_id' => $savedCollection->getId()?->value,
            'cocktail_id' => $foreignCocktail->id,
        ]);

        $reloadedCollection = $repository->findById($savedCollection->getId());

        $this->assertNotNull($reloadedCollection);
        $this->assertEqualsCanonicalizing(
            [$firstAllowedCocktail->id, $secondAllowedCocktail->id],
            $this->extractCocktailIds($reloadedCollection),
        );
    }

    public function test_it_updates_existing_collection_and_resyncs_cocktails(): void
    {
        $membership = $this->setupBarMembership();
        $repository = new EloquentCocktailCollectionRepository();
        $originalCocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Old fashioned']);
        $replacementCocktail = Cocktail::factory()->for($membership->bar)->create(['name' => 'Boulevardier']);
        $foreignCocktail = Cocktail::factory()->create();

        $model = ModelCollection::factory()->for($membership)->create([
            'name' => 'Original collection',
            'description' => 'Original description',
            'is_bar_shared' => false,
        ]);
        $model->cocktails()->attach($originalCocktail->id);

        $collection = $repository->findById(new CollectionId($model->id));

        $this->assertNotNull($collection);

        $collection->updateDetails(
            name: Name::fromString('Updated collection'),
            description: 'Updated description',
            isBarShared: true,
        );
        $collection->syncCocktails([
            new CocktailId($replacementCocktail->id),
            new CocktailId($foreignCocktail->id),
        ]);

        $savedCollection = $repository->save($collection);

        $this->assertSame($model->id, $savedCollection->getId()?->value);
        $this->assertSame('Updated collection', $savedCollection->getName()->toString());
        $this->assertSame('Updated description', $savedCollection->getDescription());
        $this->assertTrue($savedCollection->isBarShared());
        $this->assertNotNull($savedCollection->getRecordTimestamps()->getUpdatedAt());
        $this->assertEqualsCanonicalizing([$replacementCocktail->id], $this->extractCocktailIds($savedCollection));

        $this->assertDatabaseHas('collections', [
            'id' => $model->id,
            'name' => 'Updated collection',
            'description' => 'Updated description',
            'is_bar_shared' => true,
        ]);
        $this->assertDatabaseMissing('collections_cocktails', [
            'collection_id' => $model->id,
            'cocktail_id' => $originalCocktail->id,
        ]);
        $this->assertDatabaseHas('collections_cocktails', [
            'collection_id' => $model->id,
            'cocktail_id' => $replacementCocktail->id,
        ]);
        $this->assertDatabaseMissing('collections_cocktails', [
            'collection_id' => $model->id,
            'cocktail_id' => $foreignCocktail->id,
        ]);
    }

    public function test_it_deletes_collection(): void
    {
        $membership = $this->setupBarMembership();
        $cocktail = Cocktail::factory()->for($membership->bar)->create();
        $model = ModelCollection::factory()->for($membership)->create();
        $model->cocktails()->attach($cocktail->id);
        $repository = new EloquentCocktailCollectionRepository();

        $repository->delete(new CollectionId($model->id));

        $this->assertDatabaseMissing('collections', ['id' => $model->id]);
        $this->assertDatabaseMissing('collections_cocktails', [
            'collection_id' => $model->id,
            'cocktail_id' => $cocktail->id,
        ]);
    }

    /**
     * @return int[]
     */
    private function extractCocktailIds(CocktailCollection $collection): array
    {
        return array_map(
            static fn (CocktailId $cocktailId): int => $cocktailId->value,
            $collection->getCocktailIds(),
        );
    }
}
