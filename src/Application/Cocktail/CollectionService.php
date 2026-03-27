<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Bar\MemberId;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CollectionId;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\Cocktail\CocktailCollection;
use BarAssistant\Application\Cocktail\DTO\CollectionResult;
use BarAssistant\Domain\Cocktail\CocktailCollectionRepository;
use BarAssistant\Application\Exception\EntityNotFoundException;
use BarAssistant\Application\Cocktail\DTO\CreateCollectionRequest;
use BarAssistant\Application\Cocktail\DTO\UpdateCollectionRequest;
use BarAssistant\Application\Cocktail\DTO\SyncCollectionCocktailsRequest;

final readonly class CollectionService
{
    public function __construct(
        private CocktailCollectionRepository $collectionRepository,
    ) {
    }

    public function createCollection(CreateCollectionRequest $request): CollectionResult
    {
        $collection = CocktailCollection::create(
            barId: new BarId($request->barId),
            memberId: new MemberId($request->memberId),
            name: Name::fromString($request->name),
            recordTimestamps: RecordTimestamps::createdNow(),
            description: $request->description,
            isBarShared: $request->isBarShared,
        );

        $collection->syncCocktails($this->toCocktailIds($request->cocktailIds));

        $collection = $this->collectionRepository->save($collection);

        return CollectionResult::fromCollection($collection);
    }

    public function updateCollection(UpdateCollectionRequest $request): CollectionResult
    {
        $collection = $this->collectionRepository->findById(new CollectionId($request->collectionId));
        if ($collection === null) {
            throw new EntityNotFoundException('Collection not found');
        }

        $collection->updateDetails(
            name: Name::fromString($request->name),
            description: $request->description,
            isBarShared: $request->isBarShared,
        );

        $collection = $this->collectionRepository->save($collection);

        return CollectionResult::fromCollection($collection);
    }

    public function syncCocktails(SyncCollectionCocktailsRequest $request): CollectionResult
    {
        $collection = $this->collectionRepository->findById(new CollectionId($request->collectionId));
        if ($collection === null) {
            throw new EntityNotFoundException('Collection not found');
        }

        $collection->syncCocktails($this->toCocktailIds($request->cocktailIds));

        $collection = $this->collectionRepository->save($collection);

        return CollectionResult::fromCollection($collection);
    }

    public function deleteCollection(int $collectionId): void
    {
        $id = new CollectionId($collectionId);
        $collection = $this->collectionRepository->findById($id);
        if ($collection === null) {
            throw new EntityNotFoundException('Collection not found');
        }

        $this->collectionRepository->delete($id);
    }

    /**
     * @param int[] $cocktailIds
     * @return CocktailId[]
     */
    private function toCocktailIds(array $cocktailIds): array
    {
        $converted = [];

        foreach ($cocktailIds as $cocktailId) {
            $converted[] = new CocktailId($cocktailId);
        }

        return $converted;
    }
}
