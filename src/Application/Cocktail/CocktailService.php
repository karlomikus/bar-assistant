<?php

declare(strict_types=1);

namespace BarAssistant\Application\Cocktail;

use BarAssistant\Application\Cocktail\DTO\CreateCocktail;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\Cocktail;
use BarAssistant\Domain\Cocktail\CocktailRepository;
use BarAssistant\Domain\Common\Authors;
use BarAssistant\Domain\Common\Name;
use BarAssistant\Domain\Common\RecordTimestamps;
use BarAssistant\Domain\User\UserId;

final readonly class CocktailService
{
    public function __construct(private CocktailRepository $cocktailRepository)
    {
    }

    public function createCocktail(CreateCocktail $request): void
    {
        $cocktail = Cocktail::create(
            barId: new BarId(142),
            name: Name::fromString($request->name),
            instructions: $request->instructions,
            authors: Authors::createdBy(new UserId($request->userId)),
            recordTimestamps: RecordTimestamps::createdNow(),
            ingredients: [],
        );

        $this->cocktailRepository->save($cocktail);
    }
}
