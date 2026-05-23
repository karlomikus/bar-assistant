<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\CocktailMatch;
use BarAssistant\Domain\Cocktail\CocktailMatchRepository;
use BarAssistant\Application\Matcher\DTO\CocktailMatchRequest;

final class CocktailMatcher
{
    /** @var array<string, CocktailMatch> */
    private array $matchedCocktails = [];

    public function __construct(
        private CocktailMatchRepository $repository,
    ) {
    }

    public function matchByName(CocktailMatchRequest $request): ?int
    {
        $matchName = mb_strtolower($request->cocktailName);

        if (isset($this->matchedCocktails[$matchName])) {
            return $this->matchedCocktails[$matchName]->getId()->value;
        }

        $cocktails = $this->repository->findManyByBarId(new BarId(($request->barId)));
        foreach ($cocktails as $cocktail) {
            $this->matchedCocktails[$cocktail->getName()->toLowercase()] = $cocktail;
        }

        $existingCocktail = $this->matchedCocktails[$matchName] ?? null;
        if ($existingCocktail) {
            $this->matchedCocktails[$matchName] = $existingCocktail;

            return $existingCocktail->getId()->value;
        }

        return null;
    }
}
