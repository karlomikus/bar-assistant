<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use Illuminate\Support\Str;
use BarAssistant\Domain\Common\Slug;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\CocktailId;
use BarAssistant\Domain\Cocktail\CocktailRepository;

final class InMemoryCocktailRepository implements CocktailRepository
{
    private int $nextId;

    /**
     * @param array<int, Cocktail> $items
     */
    public function __construct(private array $items = [])
    {
        $this->nextId = $items === [] ? 1 : (max(array_keys($items)) + 1);
    }

    public function findById(CocktailId $id): ?Cocktail
    {
        return $this->items[$id->value] ?? null;
    }

    public function save(Cocktail $cocktail): Cocktail
    {
        if ($cocktail->isTransient()) {
            $cocktail->setId(new CocktailId($this->nextId++));
        }

        if ($cocktail->getSlug() === null) {
            $cocktail->setSlug(Slug::fromString(Str::slug($cocktail->getName()->toString())));
        }

        /** @var CocktailId $id */
        $id = $cocktail->getId();
        $this->items[$id->value] = $cocktail;

        return $cocktail;
    }
}
