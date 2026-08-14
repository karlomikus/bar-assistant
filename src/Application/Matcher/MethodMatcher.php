<?php

declare(strict_types=1);

namespace BarAssistant\Application\Matcher;

use BarAssistant\Domain\Bar\BarId;
use BarAssistant\Domain\Cocktail\CocktailMethod;
use BarAssistant\Domain\Cocktail\CocktailMethodRepository;
use BarAssistant\Application\Matcher\DTO\MethodMatchRequest;

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

        if (isset($this->matchedMethods[$matchName]) && !$this->matchedMethods[$matchName]->isTransient()) {
            return $this->matchedMethods[$matchName]->getId()->value;
        }

        $methods = $this->methodRepository->findAllInBar(new BarId($request->barId));
        foreach ($methods as $method) {
            $this->matchedMethods[$method->getName()->toLowercase()] = $method;
        }

        $existingMethod = $this->matchedMethods[$matchName] ?? null;
        if ($existingMethod) {
            $this->matchedMethods[$matchName] = $existingMethod;

            return $existingMethod->getId()?->value;
        }

        return null;
    }
}
