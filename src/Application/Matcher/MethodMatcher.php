<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher;

use BarAssistant\Application\Matcher\DTO\MethodMatchRequest;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;

final class MethodMatcher
{
    /** @var array<string, CocktailMethod> */
    private array $matchedMethods = [];

    public function __construct(
        private CocktailMethodRepository $methodRepository,
    ) {
    }

    public function matchByName(MethodMatchRequest $request): ?int
    {
        $matchName = mb_strtolower($request->methodName);

        if (isset($this->matchedMethods[$matchName])) {
            return $this->matchedMethods[$matchName]->getId()->value;
        }

        $this->matchedMethods = $this->methodRepository->findAllInBar(new BarId($request->barId));

        $existingMethod = $this->matchedMethods[$matchName] ?? null;
        if ($existingMethod) {
            $this->matchedMethods[$matchName] = $existingMethod;

            return $existingMethod->getId()->value;
        }

        return null;
    }
}
